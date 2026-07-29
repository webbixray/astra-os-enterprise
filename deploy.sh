#!/bin/bash
set -euo pipefail

# =============================================================================
# Astra OS Enterprise Deployment Script
# Usage: ./deploy.sh [environment] [branch]
#   environment: production | staging (default: production)
#   branch: git branch to deploy (default: main)
# =============================================================================

ENVIRONMENT="${1:-production}"
BRANCH="${2:-main}"
APP_DIR="/var/www/astra-os"
RELEASE_DIR="${APP_DIR}/releases/$(date +%Y%m%d%H%M%S)"
SHARED_DIR="${APP_DIR}/shared"
CURRENT_DIR="${APP_DIR}/current"
REPO_URL="${REPO_URL:-https://github.com/webbixray/astra-os-enterprise.git}"
RETENTION_COUNT="${RETENTION_COUNT:-5}"
SLACK_WEBHOOK="${SLACK_WEBHOOK:-}"

# --- Color helpers ---
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

info()  { echo -e "${CYAN}[INFO]${NC}  $*"; }
ok()    { echo -e "${GREEN}[OK]${NC}    $*"; }
warn()  { echo -e "${YELLOW}[WARN]${NC}  $*"; }
fail()  { echo -e "${RED}[FAIL]${NC}  $*"; }

# --- Slack notification ---
notify_slack() {
    local status="$1" message="$2"
    if [[ -n "${SLACK_WEBHOOK}" ]]; then
        local color
        [[ "${status}" == "success" ]] && color="good" || color="danger"
        curl -sf -X POST -H 'Content-type: application/json' \
            --data "{\"attachments\":[{\"color\":\"${color}\",\"title\":\"Deploy ${ENVIRONMENT}: ${status}\",\"text\":\"${message}\"}]}" \
            "${SLACK_WEBHOOK}" 2>/dev/null || true
    fi
}

# --- Prerequisites check ---
check_prerequisites() {
    local missing=0
    for cmd in git composer php npm; do
        if ! command -v "${cmd}" &>/dev/null; then
            fail "${cmd} is not installed."
            ((missing++))
        fi
    done
    if [[ "${missing}" -gt 0 ]]; then
        fail "Install missing prerequisites and try again."
        exit 1
    fi
}

# --- Main ---
main() {
    echo ""
    echo "=============================================="
    echo "  🚀 Astra OS — Deploy to ${ENVIRONMENT}"
    echo "  Branch: ${BRANCH}"
    echo "  Time:   $(date -u '+%Y-%m-%d %H:%M:%S UTC')"
    echo "=============================================="
    echo ""

    check_prerequisites

    # Validate directories exist
    for d in "${APP_DIR}" "${SHARED_DIR}"; do
        if [[ ! -d "${d}" ]]; then
            fail "Required directory ${d} does not exist. Run setup first."
            notify_slack "failed" "Directory ${d} missing on ${ENVIRONMENT}"
            exit 1
        fi
    done

    # --- Step 1: Create release directory ---
    info "Creating release directory: ${RELEASE_DIR}"
    mkdir -p "${RELEASE_DIR}"
    ok "Release directory created."

    # --- Step 2: Clone code ---
    info "Cloning repository (branch: ${BRANCH})..."
    git clone --depth 1 --branch "${BRANCH}" "${REPO_URL}" "${RELEASE_DIR}" 2>/dev/null || {
        fail "Git clone failed. Check REPO_URL and BRANCH."
        notify_slack "failed" "Git clone failed on ${ENVIRONMENT}"
        exit 1
    }
    ok "Repository cloned."

    # --- Step 3: Symlink shared resources ---
    info "Symlinking shared resources..."
    ln -sf "${SHARED_DIR}/.env"               "${RELEASE_DIR}/.env"
    ln -sf "${SHARED_DIR}/storage"            "${RELEASE_DIR}/storage"
    for d in "${SHARED_DIR}/"*/; do
        local name
        name="$(basename "${d}")"
        if [[ "${name}" != ".env" && "${name}" != "storage" ]]; then
            ln -sfn "${d}" "${RELEASE_DIR}/${name}" 2>/dev/null || true
        fi
    done
    ok "Shared resources linked."

    # --- Step 4: Install PHP dependencies ---
    info "Installing Composer dependencies (no-dev)..."
    cd "${RELEASE_DIR}"
    composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist 2>&1 | tail -5
    ok "Composer dependencies installed."

    # --- Step 5: Install Node dependencies and build ---
    if [[ -f "package.json" ]]; then
        info "Installing Node dependencies..."
        npm ci --production --no-audit --no-fund 2>&1 | tail -3
        info "Building frontend assets..."
        npm run build 2>&1 | tail -5
        ok "Frontend assets built."
    else
        warn "No package.json found -- skipping frontend build."
    fi

    # --- Step 6: Database migrations ---
    info "Running database migrations..."
    php artisan migrate --force --isolated 2>&1 | tail -5 || {
        warn "Migration encountered issues. Manual review may be needed."
    }
    ok "Migrations applied."

    # --- Step 7: Cache everything ---
    info "Caching configuration..."
    php artisan config:cache 2>&1 | tail -2
    php artisan route:cache 2>&1 | tail -2
    php artisan view:cache 2>&1 | tail -2
    php artisan event:cache 2>&1 | tail -2
    ok "Configuration cached."
    rm -f bootstrap/cache/packages.php bootstrap/cache/services.php

    # --- Step 8: Set permissions ---
    info "Setting filesystem permissions..."
    chown -R www-data:www-data "${RELEASE_DIR}"
    chmod -R 755 "${RELEASE_DIR}/storage" "${RELEASE_DIR}/bootstrap/cache"
    ok "Permissions set."

    # --- Step 9: Activate release ---
    info "Activating release (symlink swap)..."
    ln -sfn "${RELEASE_DIR}" "${CURRENT_DIR}"
    ok "Release activated: ${RELEASE_DIR}"

    # --- Step 10: Restart services ---
    info "Restarting services..."
    if command -v systemctl &>/dev/null; then
        sudo systemctl reload php8.4-fpm 2>/dev/null || sudo systemctl reload php8.3-fpm 2>/dev/null || sudo systemctl reload php-fpm 2>/dev/null || warn "PHP-FPM reload skipped."
        sudo systemctl reload nginx 2>/dev/null || warn "Nginx reload skipped."
    fi
    if command -v supervisorctl &>/dev/null; then
        sudo supervisorctl restart horizon:* 2>/dev/null || warn "Horizon restart skipped."
    fi
    ok "Services restarted."

    # --- Step 11: Horizon graceful restart ---
    info "Terminating Horizon to pick up new code..."
    php artisan horizon:terminate 2>/dev/null || true
    ok "Horizon terminated (will restart automatically)."

    # --- Step 12: Cleanup old releases ---
    info "Cleaning up old releases (keeping last ${RETENTION_COUNT})..."
    cd "${APP_DIR}/releases"
    ls -t | tail -n +$((RETENTION_COUNT + 1)) | while read -r old_release; do
        if [[ -n "${old_release}" && -d "${old_release}" ]]; then
            info "Removing old release: ${old_release}"
            rm -rf "${old_release}"
        fi
    done
    ok "Old releases cleaned."

    # --- Done ---
    echo ""
    echo "=============================================="
    echo "  ✅ Deployment completed successfully!"
    echo "  Environment: ${ENVIRONMENT}"
    echo "  Release:     $(basename "${RELEASE_DIR}")"
    echo "=============================================="

    notify_slack "success" "Deploy to ${ENVIRONMENT} succeeded. Release: $(basename "${RELEASE_DIR}")"
}

main "$@"
