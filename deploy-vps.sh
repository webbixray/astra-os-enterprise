#!/bin/bash
# Astra OS Enterprise v2.1.0 - VPS Production Deployment Script
# Run this script directly on the VPS (103.181.26.234) as root or nn user

set -euo pipefail

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

log_info() { echo -e "${GREEN}[INFO]${NC} $1"; }
log_warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }

# Configuration
REPO_URL="https://github.com/webbixray/astra-os-enterprise.git"
APP_DIR="/opt/astra-os"
BRANCH="main"
DOMAIN="${DOMAIN:-astra.example.com}"
EMAIL="${EMAIL:-admin@example.com}"

log_info "Starting Astra OS v2.1.0 production deployment..."

# 1. System Requirements Check
log_info "Checking system requirements..."
command -v docker >/dev/null 2>&1 || { log_error "Docker not installed"; exit 1; }
command -v docker-compose >/dev/null 2>&1 || { log_error "Docker Compose not installed"; exit 1; }
command -v git >/dev/null 2>&1 || { log_error "Git not installed"; exit 1; }

# 2. Clone or Update Repository
log_info "Cloning/updating repository..."
if [ -d "$APP_DIR" ]; then
    cd "$APP_DIR"
    git fetch origin
    git reset --hard "origin/$BRANCH"
else
    git clone --branch "$BRANCH" "$REPO_URL" "$APP_DIR"
    cd "$APP_DIR"
fi

# 3. Create Production Environment File
log_info "Creating .env file..."
if [ ! -f .env ]; then
    cp .env.example .env
    log_warn "EDIT .env with production values before continuing!"
    log_warn "Required: APP_KEY, DB_PASSWORD, REDIS_PASSWORD, SENTRY_DSN, etc."
    exit 1
fi

# 4. Generate APP_KEY if missing
if ! grep -q '^APP_KEY=base64:' .env; then
    log_info "Generating APP_KEY..."
    # Will be generated in container
fi

# 5. Build and Start Services
log_info "Building Docker images..."
docker-compose -f docker-compose.prod.yml build --no-cache

log_info "Starting services..."
docker-compose -f docker-compose.prod.yml up -d

# 6. Wait for Database
log_info "Waiting for PostgreSQL..."
sleep 10
until docker-compose -f docker-compose.prod.yml exec -T postgres pg_isready -U "${DB_USERNAME:-astra}" >/dev/null 2>&1; do
    sleep 2
done

# 7. Run Migrations
log_info "Running database migrations..."
docker-compose -f docker-compose.prod.yml exec -T app php artisan migrate --force

# 8. Optimize Laravel
log_info "Optimizing Laravel..."
docker-compose -f docker-compose.prod.yml exec -T app php artisan config:cache
docker-compose -f docker-compose.prod.yml exec -T app php artisan route:cache
docker-compose -f docker-compose.prod.yml exec -T app php artisan view:cache
docker-compose -f docker-compose.prod.yml exec -T app php artisan event:cache
docker-compose -f docker-compose.prod.yml exec -T app php artisan storage:link

# 9. Set Permissions
log_info "Setting permissions..."
docker-compose -f docker-compose.prod.yml exec -T app chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
docker-compose -f docker-compose.prod.yml exec -T app chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 10. Health Check
log_info "Running health checks..."
sleep 5
if curl -sf http://localhost/api/v1/health >/dev/null; then
    log_info "✅ Health check passed"
else
    log_error "Health check failed"
    docker-compose -f docker-compose.prod.yml logs app
    exit 1
fi

# 11. Setup SSL with Certbot (if domain provided)
if [ "$DOMAIN" != "astra.example.com" ]; then
    log_info "Setting up SSL for $DOMAIN..."
    docker-compose -f docker-compose.prod.yml exec -T nginx certbot --nginx -d "$DOMAIN" --email "$EMAIL" --agree-tos --non-interactive
fi

# 12. Setup Cron for Scheduler
log_info "Setting up Laravel scheduler cron..."
(crontab -l 2>/dev/null | grep -v "artisan schedule:run"; echo "* * * * * cd $APP_DIR && docker-compose -f docker-compose.prod.yml exec -T app php artisan schedule:run >> /dev/null 2>&1") | crontab -

log_info "✅ Deployment complete!"
log_info "Application running at: http://localhost (or https://$DOMAIN)"
log_info "Health endpoint: http://localhost/api/v1/health"
log_info "Metrics endpoint: http://localhost/metrics"
echo ""
log_warn "Post-deployment tasks:"
log_warn "1. Configure DNS for $DOMAIN"
log_warn "2. Set up monitoring (Prometheus/Grafana/Sentry/Tempo)"
log_warn "3. Configure ExternalSecrets for AWS Secrets Manager"
log_warn "4. Set up backup verification"