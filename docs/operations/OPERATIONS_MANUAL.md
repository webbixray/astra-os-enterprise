# Operations Manual

> **Classification**: Internal - Operations & Engineering Teams
> **Last Updated**: August 2026
> **Version**: 1.0

---

## Table of Contents

1. [Daily Operations](#daily-operations)
2. [Weekly Operations](#weekly-operations)
3. [Monthly Operations](#monthly-operations)
4. [Deployment Procedures](#deployment-procedures)
5. [Database Operations](#database-operations)
6. [Queue Management](#queue-management)
7. [Cache Operations](#cache-operations)
8. [Security Operations](#security-operations)
9. [Capacity Planning](#capacity-planning)
10. [Emergency Procedures](#emergency-procedures)

---

## Daily Operations

### Morning Checklist (09:00 UTC)

```bash
# 1. Check overnight alerts
kubectl get events -n astraos --since=12h --field-selector type=Warning
# Check PagerDuty/Slack for overnight pages

# 2. Verify service health
curl -s https://api.astraos.io/api/v1/health | jq .
curl -s https://api.astraos.io/api/v1/health/readiness | jq .

# 3. Check queue health
kubectl exec -n astraos deploy/astraos-app -- php artisan horizon:status

# 4. Verify backup completion
kubectl get cronjobs -n astraos
kubectl logs -n astraos -l component=backup --since=24h | tail -20

# 5. Check resource utilization
kubectl top nodes
kubectl top pods -n astraos

# 6. Review error rates
# Grafana: Astra OS / Error Budget dashboard
```

### Evening Checklist (17:00 UTC)

```bash
# 1. Verify no critical alerts firing
# Check Slack #incidents, PagerDuty

# 2. Confirm scheduled jobs ran
kubectl get jobs -n astraos --since=24h

# 3. Check disk space
kubectl exec -n astraos deploy/astraos-app -- df -h /var/www/astraos/storage

# 4. Review slow query log
kubectl exec -n astraos deploy/astraos-app -- php artisan db:slow-queries --limit=10

# 5. Update on-call handoff notes in #incidents
```

---

## Weekly Operations

### Monday - Release & Planning

```bash
# 1. Review dependency updates
cd /var/www/astraos
composer outdated --direct --minor-only

# 2. Run security audit
composer audit

# 3. Check for pending migrations
php artisan migrate:status

# 4. Review capacity trends (Grafana)
# - CPU/Memory trends
# - Database growth
# - Queue depth trends
```

### Wednesday - Maintenance Window

```bash
# 1. Apply OS/security patches (if scheduled)
# kubectl drain node --ignore-daemonsets --delete-emptydir-data

# 2. Rotate secrets (if due)
# php artisan key:generate --force
# php artisan passport:keys --force

# 3. Database maintenance
php artisan db:optimize  # VACUUM ANALYZE

# 4. Clear old logs
php artisan log:clear --days=90

# 5. Verify backup integrity
# Test restore to staging
```

### Friday - Week Review

```bash
# 1. Incident review
# - Count by severity
# - Open action items
# - Runbook updates needed

# 2. SLO review
# - Error budget status
# - Top error contributors

# 3. Deployment retrospective
# - Deployments this week
# - Rollbacks / hotfixes
# - Customer impact

# 4. Update runbooks / documentation
```

---

## Monthly Operations

### 1st of Month - Compliance & Reporting

```bash
# 1. Generate SLA report for customers
php artisan reports:generate --type=sla --month=$(date -d 'last month' +%Y-%m)

# 2. Access review
# - Review all user roles
# - Review API tokens (revoke unused > 90 days)
# - Review database users

# 3. Vendor assessment check
# - Any contracts renewing?
# - Any certifications expiring?

# 4. Disaster recovery test (quarterly)
# - Restore backup to staging
# - Verify RTO/RPO
```

### 15th of Month - Security

```bash
# 1. Rotate encryption keys (if not automated)
# 2. Review audit logs for anomalies
# 3. Update WAF rules
# 4. Penetration test findings review
```

---

## Deployment Procedures

### Standard Deployment

```bash
# 1. Pre-deployment checks
git status  # Clean working directory
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci --production
npm run build

# 2. Run tests
php artisan test --parallel

# 3. Static analysis
php artisan pint --test
php artisan phpstan analyse

# 4. Deploy
./deploy.sh production main

# 5. Post-deployment verification
curl https://api.astraos.io/api/v1/health/readiness
php artisan horizon:status
# Check Grafana for error rate spike
```

### Hotfix Deployment

```bash
# 1. Create hotfix branch
git checkout -b hotfix/issue-123 main

# 2. Make minimal fix
# Test locally

# 3. Fast-track deployment
./deploy.sh production hotfix/issue-123

# 4. Verify fix
# Monitor for 30 minutes

# 4. Merge back to main
git checkout main
git merge hotfix/issue-123
git push origin main
git branch -d hotfix/issue-123
```

### Rollback Procedure

```bash
# 1. Quick rollback (previous release)
./rollback.sh production 1

# 2. Specific version rollback
./rollback.sh production 3  # 3 releases back

# 3. Verify
curl https://api.astraos.io/api/v1/health/readiness
kubectl get pods -n astraos -w

# 4. Post-rollback
# - Check migrations (may need manual intervention)
# - Warm cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Blue-Green Deployment (Future)

```bash
# 1. Deploy to green environment
./deploy.sh staging main  # Staging = green

# 2. Run integration tests against green
php artisan test --env=staging

# 3. Switch traffic
kubectl patch service astraos-app -n astraos -p '{"spec":{"selector":{"version":"green"}}}'

# 4. Monitor for 10 minutes

# 5. Decommission blue
kubectl scale deployment astraos-app-blue -n astraos --replicas=0
```

---

## Database Operations

### Connection Monitoring

```bash
# Check active connections
kubectl exec -n astraos deploy/astraos-app -- php artisan db:monitor

# PostgreSQL native
kubectl exec -n astraos postgresql-0 -- psql -U astraos -c "
  SELECT count(*), state FROM pg_stat_activity GROUP BY state;
"

# Check for long-running queries
kubectl exec -n astraos postgresql-0 -- psql -U astraos -c "
  SELECT pid, now() - pg_stat_activity.query_start AS duration, 
         query, state 
  FROM pg_stat_activity 
  WHERE (now() - pg_stat_activity.query_start) > interval '30 seconds'
  AND state != 'idle';
"
```

### Migration Operations

```bash
# Run migrations
php artisan migrate --force --isolated

# Check status
php artisan migrate:status

# Rollback last batch
php artisan migrate:rollback --step=1

# Rollback specific migration
php artisan migrate:rollback --path=database/migrations/2026_01_01_000001_create_campaigns_table.php

# Fresh migration (DANGER - destroys data)
php artisan migrate:fresh --force --seed
```

### Backup & Restore

```bash
# Manual backup
./scripts/backup.sh daily

# Weekly backup
./scripts/backup.sh weekly

# Monthly backup
./scripts/backup.sh monthly

# Restore from backup
./scripts/backup.sh restore /var/backups/astra-os/daily/astra-os-db-20260803_020000.sql.gz

# Verify backup integrity
gunzip -c backup.sql.gz | head -20
```

### Performance Tuning

```bash
# Analyze slow queries
kubectl exec -n astraos postgresql-0 -- psql -U astraos -c "
  SELECT query, calls, total_time, mean_time, rows
  FROM pg_stat_statements
  ORDER BY mean_time DESC
  LIMIT 20;
"

# Check index usage
kubectl exec -n astraos postgresql-0 -- psql -U astraos -c "
  SELECT schemaname, tablename, indexname, idx_scan
  FROM pg_stat_user_indexes
  ORDER BY idx_scan ASC;
"

# Vacuum analyze
php artisan db:optimize

# Check table sizes
kubectl exec -n astraos postgresql-0 -- psql -U astraos -c "
  SELECT schemaname, tablename, 
         pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) AS size
  FROM pg_tables
  WHERE schemaname = 'public'
  ORDER BY pg_total_relation_size(schemaname||'.'||tablename) DESC
  LIMIT 20;
"
```

---

## Queue Management

### Horizon Dashboard Access

```bash
# Local access via port-forward
kubectl port-forward -n astraos svc/astraos-app 8080:80
# Open http://localhost:8080/horizon
```

### Queue Operations

```bash
# Check queue status
php artisan horizon:status

# Monitor in real-time
php artisan horizon:monitor

# List failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Retry specific job
php artisan queue:retry <job-id>

# Delete failed job
php artisan queue:forget <job-id>

# Flush all failed jobs
php artisan queue:flush

# Pause queue
php artisan horizon:pause

# Resume queue
php artisan horizon:continue

# Terminate Horizon (graceful)
php artisan horizon:terminate

# Scale workers
kubectl scale deployment/queue-worker -n astraos --replicas=10
```

### Queue Health Checks

```bash
# Check queue depth
php artisan queue:monitor --show=jobs,pending,failed

# Check specific queue
php artisan queue:work --queue=high,default,low --once

# Clear stuck jobs
php artisan queue:clear
```

---

## Cache Operations

### Redis Management

```bash
# Connect to Redis
kubectl exec -n astraos redis-0 -- redis-cli

# Check memory usage
kubectl exec -n astraos redis-0 -- redis-cli INFO memory

# Check connected clients
kubectl exec -n astraos redis-0 -- redis-cli CLIENT LIST

# Flush cache (careful!)
kubectl exec -n astraos redis-0 -- redis-cli FLUSHDB

# Check keyspace
kubectl exec -n astraos redis-0 -- redis-cli INFO keyspace

# Monitor commands (debug)
kubectl exec -n astraos redis-0 -- redis-cli MONITOR
```

### Laravel Cache Operations

```bash
# Clear all cache
php artisan cache:clear

# Clear config cache
php artisan config:clear

# Clear route cache
php artisan route:clear

# Clear view cache
php artisan view:clear

# Clear compiled classes
php artisan clear-compiled

# Rebuild all caches
php artisan optimize

# Warm cache
php artisan cache:warm
```

---

## Security Operations

### Access Control

```bash
# List all users
php artisan user:list

# Show user permissions
php artisan user:permissions <user-id>

# Assign role
php artisan user:assign-role <user-id> <role>

# Revoke role
php artisan user:remove-role <user-id> <role>

# List API tokens
php artisan sanctum:tokens

# Revoke token
php artisan sanctum:revoke <token-id>
```

### Secret Rotation

```bash
# Rotate APP_KEY (requires re-encrypting all encrypted data)
php artisan key:generate --force

# Rotate Passport keys
php artisan passport:keys --force

# Rotate JWT secret (if using)
php artisan jwt:secret --force

# Rotate webhook secrets
# Update in .env and restart pods
kubectl rollout restart deployment/astraos-app -n astraos
```

### Audit Log Review

```bash
# Recent audit logs
php artisan audit:list --days=7

# Export audit logs
php artisan audit:export --from=2026-07-01 --to=2026-07-31 --format=csv

# Search audit logs
php artisan audit:search --user=<user-id> --action=delete
```

### Security Scanning

```bash
# Dependency audit
composer audit

# Code security scan
./vendor/bin/phpstan analyse --level=8

# Container scan (if using Trivy)
trivy image astra-os-api:latest
```

---

## Capacity Planning

### Resource Monitoring

```bash
# Node capacity
kubectl describe nodes | grep -A 5 "Capacity:"

# Pod resource requests/limits
kubectl get pods -n astraos -o custom-columns=NAME:.metadata.name,CPU_REQ:.spec.containers[0].resources.requests.cpu,CPU_LIM:.spec.containers[0].resources.limits.cpu,MEM_REQ:.spec.containers[0].resources.requests.memory,MEM_LIM:.spec.containers[0].resources.limits.memory

# HPA status
kubectl get hpa -n astraos
```

### Scaling Triggers

| Metric | Scale Up | Scale Down |
|--------|----------|------------|
| **CPU** | > 70% for 5min | < 30% for 15min |
| **Memory** | > 80% for 5min | < 50% for 15min |
| **Queue Depth** | > 1000 pending | < 100 for 10min |
| **Response Time (P95)** | > 2s for 5min | < 500ms for 15min |
| **Error Rate** | > 1% for 5min | < 0.1% for 30min |

### Database Capacity

```bash
# Check disk usage
kubectl exec -n astraos postgresql-0 -- df -h /var/lib/postgresql/data

# Database size
kubectl exec -n astraos postgresql-0 -- psql -U astraos -c "
  SELECT pg_size_pretty(pg_database_size('astraos'));
"

# Table growth rate (monthly)
kubectl exec -n astraos postgresql-0 -- psql -U astraos -c "
  SELECT schemaname, tablename,
         pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) AS size,
         pg_total_relation_size(schemaname||'.'||tablename) / 1024 / 1024 AS size_mb
  FROM pg_tables
  WHERE schemaname = 'public'
  ORDER BY size_mb DESC;
"
```

---

## Emergency Procedures

### Complete Service Outage

```bash
# 1. Confirm outage
curl -v https://api.astraos.io/api/v1/health

# 2. Check infrastructure
kubectl get nodes
kubectl get pods -n astraos -o wide

# 3. Check load balancer
# AWS ALB / Cloudflare status

# 4. Check database
kubectl exec -n astraos postgresql-0 -- pg_isready

# 5. Check Redis
kubectl exec -n astraos redis-0 -- redis-cli PING

# 6. If all infrastructure OK, check application
kubectl logs -n astraos deploy/astraos-app --tail=100

# 7. Emergency restart
kubectl rollout restart deployment/astraos-app -n astraos
kubectl rollout restart deployment/queue-worker -n astraos

# 8. If persistent, rollback
./rollback.sh production 1
```

### Data Loss / Corruption

```bash
# 1. STOP all writes
kubectl scale deployment/astraos-app -n astraos --replicas=0
kubectl scale deployment/queue-worker -n astraos --replicas=0

# 2. Assess damage
# Check last good backup
ls -la /var/backups/astra-os/daily/

# 3. Restore from backup
./scripts/backup.sh restore /var/backups/astra-os/daily/astra-os-db-<latest>.sql.gz

# 4. Verify data integrity
php artisan db:verify

# 5. Gradual restart
kubectl scale deployment/astraos-app -n astraos --replicas=1
# Monitor for 10 min
kubectl scale deployment/astraos-app -n astraos --replicas=3
```

### Security Breach

```bash
# 1. ISOLATE
kubectl scale deployment/astraos-app -n astraos --replicas=0

# 2. ROTATE ALL SECRETS
php artisan key:generate --force
php artisan passport:keys --force
# Update DB password in AWS Secrets Manager
# Update Redis AUTH
# Update all API keys (OpenAI, Anthropic, Meta, etc.)

# 3. REVOKE ALL TOKENS
php artisan sanctum:revoke-all

# 4. PRESERVE EVIDENCE
# Snapshot DB
aws rds create-db-snapshot --db-instance-identifier astraos-prod --db-snapshot-identifier breach-$(date +%Y%m%d-%H%M)
# Export logs
kubectl logs -n astraos deploy/astraos-app --since=24h > breach-logs-$(date +%Y%m%d).txt

# 5. NOTIFY
# Legal, DPO, Customers (per GDPR/CCPA)

# 6. INVESTIGATE & REMEDIATE
# Apply patches, WAF rules, etc.

# 7. GRADUAL RESTORATION
kubectl scale deployment/astraos-app -n astraos --replicas=1
# Enhanced monitoring
```

---

## Quick Reference: One-Liners

```bash
# Full health check
curl -s https://api.astraos.io/api/v1/health | jq . && curl -s https://api.astraos.io/api/v1/health/readiness | jq .

# Quick pod status
kubectl get pods -n astraos -o wide | grep -v Running

# Resource usage
kubectl top pods -n astraos --sort-by=memory

# Recent errors
kubectl logs -n astraos deploy/astraos-app --since=1h | grep -i "error\|exception" | tail -20

# Queue depth
kubectl exec -n astraos deploy/astraos-app -- php artisan queue:monitor --show=jobs,pending,failed

# DB connections
kubectl exec -n astraos deploy/astraos-app -- php artisan db:monitor

# Deploy status
kubectl rollout status deployment/astraos-app -n astraos

# Quick rollback
./rollback.sh production 1
```

---

## Contacts & Escalation

| Role | Contact | When to Escalate |
|------|---------|------------------|
| **On-Call Engineer** | PagerDuty / #incidents | All production issues |
| **Engineering Lead** | @eng-lead | SEV-2+, architectural decisions |
| **VP Engineering** | @vp-eng | SEV-1, customer-facing outages > 30min |
| **SRE Lead** | @sre-lead | Infrastructure, capacity, reliability |
| **Security Team** | security@astraos.io | Any security incident |
| **DPO/Legal** | legal@astraos.io | Data breach, compliance questions |

---

## Document Control

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | 2026-08-03 | Platform Team | Initial operations manual |