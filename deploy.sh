#!/bin/bash
set -euo pipefail

# Astra OS Enterprise Deployment Script
# Usage: ./deploy.sh [environment]

ENVIRONMENT="${1:-production}"
APP_DIR="/var/www/astra-os"
RELEASE_DIR="${APP_DIR}/releases/$(date +%Y%m%d%H%M%S)"
SHARED_DIR="${APP_DIR}/shared"
CURRENT_DIR="${APP_DIR}/current"

echo "🚀 Deploying to ${ENVIRONMENT}..."

# Create release directory
mkdir -p "${RELEASE_DIR}"

# Clone code
git clone --depth 1 https://github.com/webbixray/astra-os-enterprise.git "${RELEASE_DIR}"

# Symlink shared resources
ln -sf "${SHARED_DIR}/.env" "${RELEASE_DIR}/.env"
ln -sf "${SHARED_DIR}/storage" "${RELEASE_DIR}/storage"

# Install dependencies
cd "${RELEASE_DIR}"
composer install --no-dev --optimize-autoloader --no-interaction

# Build frontend (if applicable)
if [ -f "package.json" ]; then
    npm ci --production --silent 2>/dev/null || true
fi

# Run migrations
php artisan migrate --force

# Cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Set permissions
chown -R www-data:www-data "${RELEASE_DIR}"
chmod -R 755 "${RELEASE_DIR}/storage" "${RELEASE_DIR}/bootstrap/cache"

# Activate release
ln -sfn "${RELEASE_DIR}" "${CURRENT_DIR}"

# Restart services
if command -v systemctl &>/dev/null; then
    sudo systemctl reload php8.4-fpm 2>/dev/null || sudo systemctl reload php8.3-fpm 2>/dev/null || true
    sudo systemctl reload nginx 2>/dev/null || true
fi
sudo supervisorctl restart horizon:* 2>/dev/null || true

# Cleanup old releases (keep last 5)
cd "${APP_DIR}/releases" 2>/dev/null || true
ls -t | tail -n +6 | xargs -I {} rm -rf {} 2>/dev/null || true

echo "✅ Deployment completed successfully!"
