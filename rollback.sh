#!/bin/bash
set -euo pipefail

# Astra OS Enterprise Rollback Script
# Rolls back to the previous release

APP_DIR="/var/www/astra-os"
RELEASES_DIR="${APP_DIR}/releases"
CURRENT_DIR="${APP_DIR}/current"

echo "⏪ Rolling back to previous release..."

# Get current and previous releases
CURRENT_RELEASE=$(readlink -f "${CURRENT_DIR}" 2>/dev/null || echo "")
RELEASES=($(ls -t "${RELEASES_DIR}" 2>/dev/null || echo ""))

if [ ${#RELEASES[@]} -lt 2 ]; then
    echo "❌ No previous release to roll back to."
    exit 1
fi

PREVIOUS_RELEASE="${RELEASES_DIR}/${RELEASES[1]}"

echo "Current:  ${CURRENT_RELEASE}"
echo "Previous: ${PREVIOUS_RELEASE}"

# Symlink to previous release
ln -sfn "${PREVIOUS_RELEASE}" "${CURRENT_DIR}"

# Restart services
if command -v systemctl &>/dev/null; then
    sudo systemctl reload php8.4-fpm 2>/dev/null || sudo systemctl reload php8.3-fpm 2>/dev/null || true
    sudo systemctl reload nginx 2>/dev/null || true
fi
sudo supervisorctl restart horizon:* 2>/dev/null || true

echo "✅ Rolled back to: ${PREVIOUS_RELEASE}"
