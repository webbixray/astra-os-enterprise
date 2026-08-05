# Astra OS Enterprise - Verification Guide

> **Purpose**: Step-by-step verification of the production-hardened Astra OS platform
> **Version**: v2.1.0
> **Requirements**: PHP 8.4+, Composer 2.x, Node.js 20+, Docker (optional)

---

## Prerequisites

```bash
# Verify environment
php --version          # Should be 8.4.x
composer --version     # Should be 2.x
node --version         # Should be 20.x
npm --version          # Should be 10.x
docker --version       # Optional, for containerized verification
```

---

## 1. Environment Setup

```bash
# Clone and enter project
git clone https://github.com/your-org/astra-os-enterprise.git
cd astra-os-enterprise

# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install Node dependencies (frontend)
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Create SQLite database for testing (or configure PostgreSQL)
touch database/database.sqlite

# Run migrations
php artisan migrate --force

# Seed database (if seeders exist)
php artisan db:seed --force

# Build frontend assets
npm run build

# Clear and rebuild caches
php artisan optimize:clear
php artisan optimize
```

**Expected**: All commands complete without errors. `vendor/` and `node_modules/` directories created.

---

## 2. Static Analysis (PHPStan)

### Run PHPStan Level 6

```bash
# Full analysis
vendor/bin/phpstan analyse --memory-limit=2G

# With progress bar
vendor/bin/phpstan analyse --memory-limit=2G --progress

# Output errors in GitHub format (for CI)
vendor/bin/phpstan analyse --memory-limit=2G --error-format=github
```

### Expected Output

```
  ------ ------------------------------------------- 
   Line   app/Domain/.../SocialMention.php          
  ------ ------------------------------------------- 
   175    Parameter #0 $id of method reconstitute() 
  ------ ------------------------------------------- 
   
 [OK] No errors
```

**Success Criteria**: Exit code 0, "No errors" message. Any errors indicate type issues needing fixes.

---

## 3. Code Style (Laravel Pint)

### Check Style

```bash
# Dry run (check only)
vendor/bin/pint --test

# With verbose output
vendor/bin/pint --test -v
```

### Fix Style (if needed)

```bash
# Auto-fix
vendor/bin/pint

# Verify after fix
vendor/bin/pint --test
```

### Expected Output (Clean)

```
Inspecting 397 files...
All files are properly styled!
```

**Success Criteria**: Exit code 0, "All files are properly styled!"

---

## 4. Test Suite

### Full Test Suite (Parallel)

```bash
# Run all tests with coverage
php artisan test --parallel --coverage-clover coverage.xml --coverage-cobertura coverage.cobertura.xml

# Without coverage (faster)
php artisan test --parallel

# Specific test suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature
```

### Expected Output

```
  PASS  Tests\Feature\Prometheus\PrometheusMetricsTest
  ✓ metrics endpoint returns 200
  ✓ metrics endpoint contains http metrics
  ✓ metrics includes method and path labels
  ✓ metrics includes status code label
  ✓ metrics endpoint requires auth in production
  ✓ metrics includes global labels
  ✓ histogram buckets configured

  PASS  Tests\Feature\OpenTelemetry\OpenTelemetryTest
  ✓ tracer provider registered
  ✓ global tracer provider set
  ✓ can create tracer
  ✓ span creation and attributes
  ✓ exception recording on span
  ✓ middleware adds telemetry headers

  PASS  Tests\Feature\Backup\BackupCommandTest
  ✓ backup command exists
  ✓ backup config loaded
  ✓ backup run command help
  ✓ backup clean command help
  ✓ backup monitor command help
  ✓ backup disk configuration
  ✓ backup retention configuration
  ✓ backup notifications configured
  ✓ backup monitor health checks

  PASS  Tests\Feature\Queue\QueueManagementTest
  ✓ queue job dispatch
  ✓ queue job chain
  ✓ queue failed job handling
  ✓ horizon supervisor configuration
  ✓ queue job retry backoff
  ✓ queue job timeout
  ✓ queue monitoring endpoint

  ... (all 242 tests pass)

  Tests:  242 passed (242 assertions)
  Time:   45.23s
```

### Test Suite Breakdown

| Suite | Tests | Expected |
|-------|-------|----------|
| Unit | ~180 | All pass |
| Feature | ~60 | All pass |
| **Total** | **242** | **All pass** |

---

## 5. Feature-Specific Test Filters

```bash
# Prometheus metrics
php artisan test --filter=PrometheusMetricsTest --parallel

# OpenTelemetry
php artisan test --filter=OpenTelemetryTest --parallel

# Backup commands
php artisan test --filter=BackupCommandTest --parallel

# Queue management
php artisan test --filter=QueueManagementTest --parallel

# Cache operations
php artisan test --filter=CacheOperationsTest --parallel

# Database operations
php artisan test --filter=DatabaseOperationsTest --parallel

# Security headers
php artisan test --filter=SecurityHeadersTest --parallel

# Observability endpoints
php artisan test --filter=ObservabilityEndpointsTest --parallel

# Social entities
php artisan test --filter=SocialEntitiesTest --parallel

# Agent use cases
php artisan test --filter=CreateAgentUseCaseTest --parallel

# Workflow use cases
php artisan test --filter=CreateWorkflowUseCaseTest --parallel

# Eloquent models
php artisan test --filter=EloquentModelsTest --parallel
```

---

## 6. Health Endpoints Verification

```bash
# Start local server
php artisan serve --port=8000 &

# Wait for server
sleep 3

# Test health endpoints
curl -s http://localhost:8000/api/v1/health | jq .
curl -s http://localhost:8000/api/v1/health/readiness | jq .
curl -s http://localhost:8000/api/v1/health/startup | jq .

# Test Prometheus metrics
curl -s http://localhost:8000/metrics | head -50

# Kill server
kill %1
```

### Expected Health Response

```json
{
  "status": "ok",
  "timestamp": "2026-08-05T14:30:00Z",
  "version": "2.1.0",
  "service": "astra-os-enterprise"
}
```

### Expected Readiness Response

```json
{
  "status": "ok",
  "healthy": true,
  "checks": {
    "database": {"healthy": true, "message": "Database connection OK", "driver": "pgsql"},
    "cache": {"healthy": true, "message": "Cache connection OK", "driver": "redis"},
    "environment": {"healthy": true, "message": "Environment: local", "app_env": "local"}
  },
  "timestamp": "2026-08-05T14:30:00Z"
}
```

---

## 7. Metrics Verification

```bash
# Check metrics endpoint
curl -s http://localhost:8000/metrics | grep -E "astra_os_"

# Expected metrics
# astra_os_http_requests_total{method="GET",path="/api/v1/health",status="200"} 1
# astra_os_http_request_duration_seconds_bucket{method="GET",path="/api/v1/health",le="0.1"} 1
# astra_os_queue_jobs_total{job="ProcessCampaignLaunch",status="success"} 0
# astra_os_database_queries_total{connection="pgsql",type="select"} 0
```

---

## 7. Docker Verification (Optional)

```bash
# Build production image
docker build -t astra-os:local --target production .

# Run container
docker run -d --name astraos-test \
  -p 8000:80 \
  -e APP_ENV=testing \
  -e DB_CONNECTION=sqlite \
  -e DB_DATABASE=/var/www/html/database/database.sqlite \
  astra-os:local

# Wait and test
sleep 10
curl -s http://localhost:8000/api/v1/health/readiness | jq .

# Cleanup
docker stop astraos-test && docker rm astraos-test
```

---

## 8. K8s Manifest Validation

```bash
# Install kustomize (if not installed)
# brew install kustomize  # macOS
# snap install kustomize  # Linux

# Validate base manifests
kustomize build k8s/base

# Validate staging overlay
kustomize build k8s/overlays/staging

# Validate production overlay
kustomize build k8s/overlays/production
```

### Expected Output

```yaml
# Should output valid K8s manifests without errors
apiVersion: v1
kind: Namespace
metadata:
  name: astraos
---
apiVersion: apps/v1
kind: Deployment
metadata:
  name: astraos-app
  namespace: astraos
...
```

---

## 9. Security Audit

```bash
# Composer security audit
composer audit

# Should output: "No vulnerabilities found" or list issues

# Dependency review (if in PR)
# Runs automatically via GitHub Action
```

---

## 10. Frontend Build Verification

```bash
# Clean build
npm run build

# Check output
ls -la public/build/

# Expected: manifest.json, assets/ directory with hashed filenames
```

---

## ✅ Complete Verification Checklist

| Step | Command | Expected Result | ✅ |
|------|---------|-----------------|-----|
| 1 | `composer install` | No errors, vendor/ created | ☐ |
| 2 | `npm install && npm run build` | Assets built, no errors | ☐ |
| 3 | `php artisan migrate --force` | Tables created | ☐ |
| 4 | `vendor/bin/phpstan analyse --memory-limit=2G` | **No errors** | ☐ |
| 5 | `vendor/bin/pint --test` | **All files styled** | ☐ |
| 6 | `php artisan test --parallel` | **242 passed** | ☐ |
| 7 | `curl /api/v1/health` | `{"status":"ok"}` | ☐ |
| 8 | `curl /api/v1/health/readiness` | `{"healthy":true}` | ☐ |
| 9 | `curl /metrics` | Prometheus metrics | ☐ |
| 10 | `kustomize build k8s/base` | Valid K8s YAML | ☐ |
| 11 | `composer audit` | No vulnerabilities | ☐ |
| 12 | `npm run build` | Assets in public/build | ☐ |

---

## 🚨 Troubleshooting

### PHPStan Errors
```bash
# Clear cache and re-run
rm -rf .phpstan.cache
vendor/bin/phpstan analyse --memory-limit=2G --clear-result-cache
```

### Test Failures
```bash
# Run specific failing test with debug
php artisan test --filter=FailingTestName --stop-on-failure -v

# Check database
php artisan migrate:status
php artisan db:seed --force
```

### Pint Style Issues
```bash
# Auto-fix
vendor/bin/pint

# Check specific file
vendor/bin/pint --test app/Http/Controllers/PrometheusMetricsController.php
```

### Database Connection Issues
```bash
# Check .env
cat .env | grep DB_

# Verify SQLite
ls -la database/database.sqlite

# Or PostgreSQL
php artisan db:monitor
```

---

## 📊 Expected Metrics Baseline

| Metric | Target |
|--------|--------|
| **Tests** | 242 pass |
| **PHPStan** | 0 errors (Level 6) |
| **Pint** | 100% compliant |
| **Coverage** | > 80% |
| **Health endpoint** | < 50ms |
| **Readiness endpoint** | < 100ms |
| **Build time** | < 3 min |
| **Test time** | < 60s |

---

## 🎯 Sign-off Criteria

All 12 checklist items ✅ = **Production Ready**

> **Note**: This verification was designed for Astra OS v2.1.0. Run on every release candidate and before production deployment.