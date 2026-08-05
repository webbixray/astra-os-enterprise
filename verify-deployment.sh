#!/bin/bash
# Astra OS Enterprise - Post-Deployment Verification
# Run this after deployment to verify everything is working

set -euo pipefail

GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m'

log_info() { echo -e "${GREEN}[✓]${NC} $1"; }
log_error() { echo -e "${RED}[✗]${NC} $1"; }

BASE_URL="${BASE_URL:-http://localhost}"

echo "=== Astra OS v2.1.0 Post-Deployment Verification ==="
echo "Testing: $BASE_URL"
echo ""

# Health endpoints
echo "--- Health Endpoints ---"
for endpoint in "/api/v1/health" "/api/v1/health/readiness" "/api/v1/health/startup"; do
    if curl -sf "$BASE_URL$endpoint" >/dev/null; then
        log_info "$endpoint"
    else
        log_error "$endpoint"
    fi
done

# Metrics
echo ""
echo "--- Metrics ---"
if curl -sf "$BASE_URL/metrics" | grep -q "http_requests_total"; then
    log_info "/metrics (Prometheus)"
else
    log_error "/metrics (Prometheus)"
fi

# API endpoints
echo ""
echo "--- API Endpoints ---"
for endpoint in "/api/v1/organizations" "/api/v1/campaigns" "/api/v1/agents" "/api/v1/workflows"; do
    if curl -sf "$BASE_URL$endpoint" >/dev/null; then
        log_info "$endpoint"
    else
        log_error "$endpoint"
    fi
done

# Queue/Horizon
echo ""
echo "--- Queue (Horizon) ---"
if curl -sf "$BASE_URL/horizon" >/dev/null; then
    log_info "/horizon"
else
    log_error "/horizon"
fi

# Database connectivity
echo ""
echo "--- Database ---"
if curl -sf "$BASE_URL/api/v1/health/readiness" | grep -q "database.*ok"; then
    log_info "Database connectivity"
else
    log_error "Database connectivity"
fi

# Redis
echo ""
echo "--- Redis ---"
if curl -sf "$BASE_URL/api/v1/health/readiness" | grep -q "redis.*ok"; then
    log_info "Redis connectivity"
else
    log_error "Redis connectivity"
fi

echo ""
echo "=== Verification Complete ==="