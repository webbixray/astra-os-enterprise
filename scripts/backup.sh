#!/bin/bash
set -euo pipefail

# =============================================================================
# Astra OS — Backup Script (Database + Files)
# Usage:
#   ./backup.sh                  # Daily backup (default)
#   ./backup.sh weekly           # Weekly backup
#   ./backup.sh monthly          # Monthly backup
#   ./backup.sh restore <file>   # Restore from a backup archive
# =============================================================================

MODE="${1:-daily}"
RESTORE_FILE="${2:-}"

APP_NAME="${APP_NAME:-astra-os}"
APP_DIR="${APP_DIR:-/var/www/astra-os}"
BACKUP_BASE="${BACKUP_BASE:-/var/backups/astra-os}"
DB_NAME="${DB_NAME:-astra_os}"
DB_USER="${DB_USER:-astra}"
DB_HOST="${DB_HOST:-localhost}"
DB_PORT="${DB_PORT:-5432}"
DB_PASSWORD="${DB_PASSWORD:-}"
STORAGE_DIR="${STORAGE_DIR:-${APP_DIR}/storage}"
S3_BUCKET="${S3_BUCKET:-}"
S3_ENDPOINT="${S3_ENDPOINT:-}"
AWS_ACCESS_KEY_ID="${AWS_ACCESS_KEY_ID:-}"
AWS_SECRET_ACCESS_KEY="${AWS_SECRET_ACCESS_KEY:-}"
SLACK_WEBHOOK="${SLACK_WEBHOOK:-}"
PGDUMP_EXTRA="${PGDUMP_EXTRA:---no-owner --no-acl --clean --if-exists}"

# Retention periods
DAILY_RETENTION=7
WEEKLY_RETENTION=4
MONTHLY_RETENTION=12

# Date stamps
DATE_STAMP="$(date +%Y%m%d)"
TIMESTAMP="$(date +%Y%m%d_%H%M%S)"
DAY_OF_WEEK="$(date +%u)"     # 1=Mon .. 7=Sun
DAY_OF_MONTH="$(date +%d)"

BACKUP_DIR="${BACKUP_BASE}/${MODE}"
DB_DUMP_PATH="${BACKUP_DIR}/${APP_NAME}-db-${TIMESTAMP}.sql.gz"
FILES_ARCHIVE_PATH="${BACKUP_DIR}/${APP_NAME}-files-${TIMESTAMP}.tar.gz"
LOG_PATH="${BACKUP_BASE}/backup.log"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

info()  { echo -e "${CYAN}[INFO]${NC}  $(date '+%H:%M:%S')  $*" | tee -a "${LOG_PATH}"; }
ok()    { echo -e "${GREEN}[OK]${NC}    $(date '+%H:%M:%S')  $*" | tee -a "${LOG_PATH}"; }
warn()  { echo -e "${YELLOW}[WARN]${NC}  $(date '+%H:%M:%S')  $*" | tee -a "${LOG_PATH}"; }
fail()  { echo -e "${RED}[FAIL]${NC}  $(date '+%H:%M:%S')  $*" | tee -a "${LOG_PATH}" >&2; }

notify_slack() {
    local status="$1" message="$2"
    if [[ -n "${SLACK_WEBHOOK}" ]]; then
        local color
        [[ "${status}" == "success" ]] && color="good" || color="danger"
        curl -sf -X POST -H 'Content-type: application/json' \
            --data "{\"attachments\":[{\"color\":\"${color}\",\"title\":\"Backup ${APP_NAME} [${MODE}]: ${status}\",\"text\":\"${message}\"}]}" \
            "${SLACK_WEBHOOK}" 2>/dev/null || true
    fi
}

check_prerequisites() {
    local missing=0
    for cmd in pg_dump gzip tar; do
        if ! command -v "${cmd}" &>/dev/null; then
            fail "${cmd} is not installed."
            ((missing++))
        fi
    done
    if [[ -n "${S3_BUCKET}" ]]; then
        for cmd in aws curl; do
            if ! command -v "${cmd}" &>/dev/null; then
                warn "${cmd} not found — S3 upload will be skipped."
                S3_BUCKET=""
                break
            fi
        done
    fi
    if [[ "${missing}" -gt 0 ]]; then
        fail "Install missing prerequisites and try again."
        exit 1
    fi
}

# =========================================================================
# Backup: Database
# =========================================================================
backup_database() {
    info "Dumping PostgreSQL database: ${DB_NAME}@${DB_HOST}:${DB_PORT}"

    mkdir -p "${BACKUP_DIR}"

    local pg_dump_cmd="pg_dump"
    if [[ -n "${DB_PASSWORD}" ]]; then
        export PGPASSWORD="${DB_PASSWORD}"
    fi

    # Dump and compress in a pipeline
    ${pg_dump_cmd} \
        -h "${DB_HOST}" \
        -p "${DB_PORT}" \
        -U "${DB_USER}" \
        -d "${DB_NAME}" \
        ${PGDUMP_EXTRA} \
        2>>"${LOG_PATH}" \
        | gzip -9 > "${DB_DUMP_PATH}"

    unset PGPASSWORD

    local db_size
    db_size="$(du -h "${DB_DUMP_PATH}" | cut -f1)"
    ok "Database dump created: ${DB_DUMP_PATH} (${db_size})"
}

# =========================================================================
# Backup: Storage files
# =========================================================================
backup_files() {
    info "Archiving storage directory: ${STORAGE_DIR}"

    if [[ ! -d "${STORAGE_DIR}" ]]; then
        warn "Storage directory not found: ${STORAGE_DIR} — skipping file backup."
        FILES_ARCHIVE_PATH=""
        return
    fi

    tar czf "${FILES_ARCHIVE_PATH}" \
        --exclude="cache/*" \
        --exclude="debugbar/*" \
        --exclude="logs/*.gz" \
        --exclude="framework/cache/data/*" \
        --exclude="framework/sessions/*" \
        --exclude="framework/testing/*" \
        --exclude="app/public/*/cache/*" \
        -C "$(dirname "${STORAGE_DIR}")" \
        "$(basename "${STORAGE_DIR}")" \
        2>>"${LOG_PATH}"

    local files_size
    files_size="$(du -h "${FILES_ARCHIVE_PATH}" | cut -f1)"
    ok "Files archive created: ${FILES_ARCHIVE_PATH} (${files_size})"
}

# =========================================================================
# Upload to S3-compatible storage
# =========================================================================
upload_s3() {
    if [[ -z "${S3_BUCKET}" ]]; then
        info "S3 bucket not configured — skipping upload."
        return
    fi

    info "Uploading to S3: s3://${S3_BUCKET}/${APP_NAME}/${MODE}/"

    local aws_args=(
        --endpoint-url "${S3_ENDPOINT}"
    )
    if [[ -z "${AWS_ACCESS_KEY_ID}" || -z "${AWS_SECRET_ACCESS_KEY}" ]]; then
        warn "AWS credentials not set — trying IAM role / instance profile."
    fi

    aws s3 cp "${DB_DUMP_PATH}" "s3://${S3_BUCKET}/${APP_NAME}/${MODE}/" "${aws_args[@]}" 2>>"${LOG_PATH}"
    ok "Database dump uploaded to S3."

    if [[ -n "${FILES_ARCHIVE_PATH}" && -f "${FILES_ARCHIVE_PATH}" ]]; then
        aws s3 cp "${FILES_ARCHIVE_PATH}" "s3://${S3_BUCKET}/${APP_NAME}/${MODE}/" "${aws_args[@]}" 2>>"${LOG_PATH}"
        ok "Files archive uploaded to S3."
    fi
}

# =========================================================================
# Retention: prune old backups
# =========================================================================
prune_backups() {
    info "Pruning old backups (retention: ${MODE})..."

    local retention
    case "${MODE}" in
        daily)   retention="${DAILY_RETENTION}" ;;
        weekly)  retention="${WEEKLY_RETENTION}" ;;
        monthly) retention="${MONTHLY_RETENTION}" ;;
        *)       retention="${DAILY_RETENTION}" ;;
    esac

    for type in db files; do
        local pattern
        if [[ "${type}" == "db" ]]; then
            pattern="${BACKUP_DIR}/${APP_NAME}-db-*.sql.gz"
        else
            pattern="${BACKUP_DIR}/${APP_NAME}-files-*.tar.gz"
        fi

        local count
        count="$(ls -1 ${pattern} 2>/dev/null | wc -l)"
        if [[ "${count}" -le "${retention}" ]]; then
            ok "${type}: ${count} backup(s), within retention (${retention})."
            continue
        fi

        local to_delete=$((count - retention))
        info "${type}: pruning ${to_delete} of ${count} backup(s)..."
        ls -t ${pattern} 2>/dev/null | tail -n "${to_delete}" | while read -r old; do
            rm -f "${old}"
            info "  Removed: ${old}"
        done
        ok "${type}: pruned ${to_delete} old backup(s)."
    done

    # Prune S3 as well
    if [[ -n "${S3_BUCKET}" ]]; then
        info "Pruning S3 backups..."
        local s3_prefix="${APP_NAME}/${MODE}/"
        local s3_objects
        s3_objects="$(aws s3 ls "s3://${S3_BUCKET}/${s3_prefix}" --endpoint-url "${S3_ENDPOINT}" 2>/dev/null | wc -l || echo 0)"
        if [[ "${s3_objects}" -gt "${retention}" ]]; then
            local s3_to_delete=$((s3_objects - retention))
            aws s3 ls "s3://${S3_BUCKET}/${s3_prefix}" --endpoint-url "${S3_ENDPOINT}" 2>/dev/null \
                | sort \
                | head -n "${s3_to_delete}" \
                | while read -r line; do
                    local key
                    key="$(echo "${line}" | awk '{print $4}')"
                    if [[ -n "${key}" ]]; then
                        aws s3 rm "s3://${S3_BUCKET}/${s3_prefix}${key}" --endpoint-url "${S3_ENDPOINT}" 2>/dev/null || true
                    fi
                done
            ok "S3: pruned old backups."
        fi
    fi
}

# =========================================================================
# Restore from backup
# =========================================================================
restore_from_backup() {
    local archive="${RESTORE_FILE}"
    if [[ ! -f "${archive}" ]]; then
        fail "Restore file not found: ${archive}"
        echo "Usage: $0 restore <path-to-backup-file>"
        exit 1
    fi

    info "Starting restore from: ${archive}"
    echo ""
    warn "⚠  RESTORE WILL OVERWRITE THE CURRENT DATABASE!"
    if [[ -t 0 ]]; then
        read -r -p "Are you sure? Type 'yes' to continue: " confirm
        if [[ "${confirm}" != "yes" ]]; then
            info "Restore cancelled."
            exit 0
        fi
    fi

    local ext="${archive##*.}"
    local base="${archive%.*}"

    case "${archive}" in
        *.sql.gz)
            info "Restoring PostgreSQL database..."
            if [[ -n "${DB_PASSWORD}" ]]; then
                export PGPASSWORD="${DB_PASSWORD}"
            fi
            gunzip -c "${archive}" | psql \
                -h "${DB_HOST}" \
                -p "${DB_PORT}" \
                -U "${DB_USER}" \
                -d "${DB_NAME}" \
                2>&1 | tee -a "${LOG_PATH}"
            unset PGPASSWORD
            ok "Database restored from ${archive}."
            ;;
        *.tar.gz|*.tgz)
            info "Restoring storage files..."
            local restore_target="${APP_DIR}/"
            tar xzf "${archive}" -C "${restore_target}" 2>&1 | tee -a "${LOG_PATH}"
            chown -R www-data:www-data "${STORAGE_DIR}"
            ok "Files restored from ${archive}."
            ;;
        *)
            fail "Unknown backup format: ${archive} (expected .sql.gz or .tar.gz)"
            exit 1
            ;;
    esac
}

# =========================================================================
# Main
# =========================================================================
main() {
    mkdir -p "${BACKUP_BASE}" "${BACKUP_DIR}"
    touch "${LOG_PATH}"

    echo ""
    echo "=============================================="
    echo "  💾 Astra OS — Backup [${MODE}]"
    echo "  Time: $(date -u '+%Y-%m-%d %H:%M:%S UTC')"
    echo "=============================================="
    echo ""

    check_prerequisites

    case "${MODE}" in
        restore)
            restore_from_backup
            notify_slack "success" "Restore completed from ${RESTORE_FILE}"
            ;;
        daily|weekly|monthly)
            backup_database
            backup_files
            upload_s3
            prune_backups

            local total_size
            total_size="$(du -sh "${BACKUP_DIR}" | cut -f1)"
            echo ""
            echo "=============================================="
            echo "  ✅ Backup [${MODE}] completed!"
            echo "  Location: ${BACKUP_DIR}"
            echo "  Total size: ${total_size}"
            echo "=============================================="

            notify_slack "success" "[${MODE}] Backup completed. Size: ${total_size}, DB: ${DB_DUMP_PATH}"
            ;;
        *)
            echo "Usage: $0 {daily|weekly|monthly|restore <file>}"
            exit 1
            ;;
    esac
}

main "$@"
