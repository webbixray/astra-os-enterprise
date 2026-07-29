#!/bin/bash
set -euo pipefail

# =============================================================================
# Astra OS Enterprise Rollback Script
# Reverts the current release to a previous release.
# Usage: ./rollback.sh [environment] [steps]
#   environment: production | staging (default: production)
#   steps: number of releases to roll back (default: 1)
# =============================================================================

ENVIRONMENT="${1:-production}"
STEPS="${2:-1}"
APP_DIR="/var/www/astra-os"
RELEASES_DIR="${APP_DIR}/releases"
CURRENT_DIR="${APP_DIR}/current"
SHARED_DIR="${APP_DIR}/shared"
SLACK_WEBHOOK="${SLACK_WEBHOOK:-}"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

info()  { echo -e "${CYAN}[INFO]${NC}  $*"; }
ok()    { echo -e "${GREEN}[OK]${NC}    $*"; }
warn()  { echo -e "${YELLOW}[WARN]${NC}  $*"; }
fail()  { echo -e "${RED}[FAIL]${NC}  $*"; }

notify_slack() {
    local status="$1" message="$2"
    if [[ -n "${SLACK_WEBHOOK}" ]]; then
        local color
        [[ "${status}" == "success" ]] && color="good" || color="danger"
        curl -sf -X POST -H 'Content-type: application/json' \
            --data "{\"attachments\":[{\"color\":\"${color}\",\"title\":\"Rollback ${ENVIRONMENT}: ${status}\",\"text\":\"${message}\"}]}" \
            "${SLACK_WEBHOOK}" 2>/dev/null || true
    fi
}

main() {
    echo ""
    echo "=============================================="
    echo "  ⏪ Astra OS -- Rollback on ${ENVIRONMENT}"
    echo "  Steps: ${STEPS}"
    echo "  Time:  $(date -u '+%Y-%m-%d %H:%M:%S UTC')"
    echo "=============================================="
    echo ""

    # --- Validate directories ---
    if [[ ! -d "${RELEASES_DIR}" ]]; then
        fail "Releases directory not found: ${RELEASES_DIR}"
        exit 1
    fi
    if [[ ! -L "${CURRENT_DIR}" ]]; then
        fail "Current symlink not found: ${CURRENT_DIR}"
        exit 1
    fi

    # --- Get current and previous releases ---
    local releases
    mapfile -t releases < <(ls -td "${RELEASES_DIR}"/*/ 2>/dev/null || true)

    local release_count="${#releases[@]}"
    if [[ "${release_count}" -lt 2 ]]; then
        fail "Not enough releases to roll back. Found ${release_count} release(s), need at least 2."
        notify_slack "failed" "Rollback failed: insufficient releases (${release_count}) on ${ENVIRONMENT}"
        exit 1
    fi

    local current_release
    current_release="$(readlink -f "${CURRENT_DIR}")"
    info "Current release: ${current_release}"

    local target_index=$((STEPS))
    if [[ "${target_index}" -ge "${release_count}" ]]; then
        fail "Cannot roll back ${STEPS} step(s): only ${release_count} releases available."
        notify_slack "failed" "Rollback failed: requested ${STEPS} steps, ${release_count} releases on ${ENVIRONMENT}"
        exit 1
    fi

    local target_release
    target_release="${releases[${target_index}]}"
    target_release="${target_release%/}"

    info "Target release: ${target_release}"

    # --- Confirmation prompt (skip if non-interactive) ---
    if [[ -t 0 ]]; then
        echo ""
        warn "You are about to roll back from:"
        echo "    $(basename "$(readlink -f "${CURRENT_DIR}")")"
        echo "    -> $(basename "${target_release}")"
        echo ""
        read -r -p "Continue? [y/N] " confirm
        if [[ "${confirm}" != "y" && "${confirm}" != "Y" ]]; then
            info "Rollback cancelled."
            exit 0
        fi
    fi

    # --- Step 1: Record the rollback in a marker file ---
    local rollback_marker="${RELEASES_DIR}/.rollback_log"
    echo "$(date -u '+%Y-%m-%d %H:%M:%S') | rolled back from $(basename "$(readlink -f "${CURRENT_DIR}")") to $(basename "${target_release}")" >> "${rollback_marker}"

    # --- Step 2: Symlink the previous release ---
    info "Switching symlink to target release..."
    ln -sfn "${target_release}" "${CURRENT_DIR}"
    ok "Symlink updated: ${CURRENT_DIR} -> ${target_release}"

    # --- Step 3: Re-link shared resources ---
    info "Ensuring shared resources are linked correctly..."
    ln -sf "${SHARED_DIR}/.env"    "${target_release}/.env"    2>/dev/null || true
    ln -sf "${SHARED_DIR}/storage" "${target_release}/storage" 2>/dev/null || true
    ok "Shared resources re-linked."

    # --- Step 4: Run migrations for the rolled-back release ---
    if [[ -f "${target_release}/artisan" ]]; then
        info "Running database migrations..."
        cd "${target_release}"
        php artisan migrate --force --isolated 2>&1 | tail -5 || warn "Migration had issues -- manual verification recommended."
        ok "Database migrations synced."

        info "Rebuilding cache for rolled-back release..."
        php artisan config:cache 2>&1 | tail -2
        php artisan route:cache 2>&1 | tail -2
        php artisan view:cache 2>&1 | tail -2
        php artisan event:cache 2>&1 | tail -2
        ok "Cache rebuilt."
    fi

    # --- Step 5: Set permissions ---
    info "Setting permissions..."
    chown -R www-data:www-data "${target_release}"
    chmod -R 755 "${target_release}/storage" "${target_release}/bootstrap/cache"
    ok "Permissions set."

    # --- Step 6: Restart services ---
    info "Restarting services..."
    if command -v systemctl &>/dev/null; then
        sudo systemctl reload php8.4-fpm 2>/dev/null || sudo systemctl reload php-fpm 2>/dev/null || warn "PHP-FPM reload skipped."
        sudo systemctl reload nginx 2>/dev/null || warn "Nginx reload skipped."
    fi
    if command -v supervisorctl &>/dev/null; then
        sudo supervisorctl restart horizon:* 2>/dev/null || warn "Horizon restart skipped."
    fi
    php "${target_release}/artisan" horizon:terminate 2>/dev/null || true
    ok "Services restarted."

    # --- Done ---
    echo ""
    echo "=============================================="
    echo "  ✅ Rollback completed successfully!"
    echo "  Rolled back to: $(basename "${target_release}")"
    echo "=============================================="

    notify_slack "success" "Rollback on ${ENVIRONMENT} completed. Reverted to $(basename "${target_release}")."
}

main "$@"
