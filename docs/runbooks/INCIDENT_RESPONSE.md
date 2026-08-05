# Incident Response Runbook

> **Classification**: Internal - Operations Team
> **Last Updated**: August 2026
> **Version**: 1.0

---

## Table of Contents

1. [Incident Severity Classification](#incident-severity-classification)
2. [Escalation Matrix](#escalation-matrix)
3. [Common Incident Scenarios](#common-incident-scenarios)
4. [Communication Protocols](#communication-protocols)
5. [Post-Incident Process](#post-incident-process)

---

## Incident Severity Classification

| Severity | Level | Description | Response Time | Examples |
|----------|-------|-------------|---------------|----------|
| **SEV-1** | Critical | Complete service outage, data loss, security breach | **15 minutes** | Database down, API returning 5xx > 50%, data corruption, active exploit |
| **SEV-2** | Major | Degraded performance, partial outage, non-critical feature broken | **30 minutes** | Slow queries > 5s, queue backlog > 10k, one region down, payments failing |
| **SEV-3** | Minor | Minor issue, workaround exists, cosmetic bug | **2 hours** | UI glitch, slow dashboard, non-critical report failing, email delays |
| **SEV-4** | Low | Enhancement, documentation, non-urgent maintenance | **Next business day** | Feature request, typo, minor optimization, dependency update |

---

## Escalation Matrix

### SEV-1 (Critical)
```
T+0min    → On-call engineer paged (PagerDuty/OpsGenie)
T+5min    → On-call acknowledges, starts investigation
T+15min   → If no progress → Page engineering lead
T+30min   → If no resolution → Page VP Engineering / CTO
T+60min   → If no resolution → Activate war room, all hands
T+2hrs    → If no resolution → Executive notification, customer communication
```

### SEV-2 (Major)
```
T+0min    → On-call engineer notified (Slack/email)
T+15min   → On-call acknowledges
T+30min   → If no progress → Page engineering lead
T+2hrs    → If no resolution → Escalate to team lead
```

### SEV-3/SEV-4
```
Standard ticket triage during business hours
Assigned to relevant team within 4 business hours
```

---

## Common Incident Scenarios

### 1. Database Connection Exhaustion

**Symptoms**: `SQLSTATE[08006] [7] FATAL: remaining connection slots reserved`  
**Impact**: All database operations fail  
**Runbook**:

```bash
# 1. Check current connections
kubectl exec -n astraos deploy/astraos-app -- php artisan db:monitor

# 2. Check PgBouncer status (if deployed)
kubectl exec -n astraos pgbouncer-0 -- pgbouncer -c /etc/pgbouncer/pgbouncer.ini -R

# 3. Identify connection leak source
kubectl logs -n astraos deploy/astraos-app --since=10m | grep -i "connection\|pdo"

# 4. Quick fix: Restart app pods to release connections
kubectl rollout restart deployment/astraos-app -n astraos

# 5. Long term: Increase max_connections or add PgBouncer
# Edit RDS parameter group: max_connections = 500
```

**Prevention**: Monitor `postgresql_connections_active` metric, alert at 80% capacity.

---

### 2. Queue Backlog / Job Failures

**Symptoms**: Horizon shows > 10k pending jobs, `failed_jobs` table growing  
**Impact**: Async operations (emails, AI tasks, webhooks) delayed/failed  
**Runbook**:

```bash
# 1. Check queue status
kubectl exec -n astraos deploy/astraos-app -- php artisan horizon:status

# 2. Inspect failed jobs
kubectl exec -n astraos deploy/astraos-app -- php artisan queue:failed

# 3. Retry failed jobs (if transient)
kubectl exec -n astraos deploy/astraos-app -- php artisan queue:retry all

# 4. Scale workers
kubectl scale deployment/queue-worker -n astraos --replicas=10

# 5. If specific job type failing, investigate logs
kubectl logs -n astraos deploy/queue-worker --since=30m | grep -i "error\|exception"

# 6. Clear stuck jobs if needed
kubectl exec -n astraos deploy/astraos-app -- php artisan queue:flush
```

**Prevention**: Alert on `queue_jobs_pending > 5000` and `queue_job_failed_total` rate.

---

### 3. High Memory / OOM Kills

**Symptoms**: Pods restarting with `OOMKilled`, increased 5xx errors  
**Impact**: Service degradation, request failures  
**Runbook**:

```bash
# 1. Check pod resource usage
kubectl top pods -n astraos

# 2. Check OOM events
kubectl get events -n astraos --field-selector reason=OOMKilling

# 3. Analyze memory profile (if Xdebug/Blackfire available)
# Or check for memory leaks in recent deployments

# 4. Quick fix: Increase memory limits
kubectl patch deployment astraos-app -n astraos -p '{"spec":{"template":{"spec":{"containers":[{"name":"app","resources":{"limits":{"memory":"2Gi"}}}]}}}}'

# 5. Restart pods with new limits
kubectl rollout restart deployment/astraos-app -n astraos
```

**Prevention**: Alert on `container_memory_usage_bytes / container_spec_memory_limit_bytes > 0.85`.

---

### 4. Redis Connection Issues

**Symptoms**: `READONLY You can't write against a read only replica`, connection timeouts  
**Impact**: Cache misses, session loss, queue failures  
**Runbook**:

```bash
# 1. Check Redis status
kubectl exec -n astraos redis-0 -- redis-cli INFO replication

# 2. Check for failover in progress
kubectl logs -n astraos redis-0 --since=10m

# 3. If using ElastiCache, check AWS console for failover events

# 4. Restart app pods to force reconnection
kubectl rollout restart deployment/astraos-app -n astraos

# 5. Verify cache warming
kubectl exec -n astraos deploy/astraos-app -- php artisan cache:warm
```

---

### 5. SSL Certificate Expiry

**Symptoms**: Browser warnings, cert-manager events showing expiry  
**Impact**: HTTPS broken, trust loss  
**Runbook**:

```bash
# 1. Check certificate status
kubectl get certificates -n astraos
kubectl describe certificate astraos-tls -n astraos

# 2. Check cert-manager logs
kubectl logs -n cert-manager deploy/cert-manager --since=1h

# 3. Force renewal
kubectl delete secret astraos-tls -n astraos
# cert-manager will recreate

# 4. If Let's Encrypt rate limited, use staging issuer temporarily
```

---

### 6. Deployment Rollback Required

**Symptoms**: New deployment causing errors, health checks failing  
**Impact**: Service degradation  
**Runbook**:

```bash
# 1. Check rollout status
kubectl rollout status deployment/astraos-app -n astraos

# 2. View rollout history
kubectl rollout history deployment/astraos-app -n astraos

# 3. Rollback to previous version
kubectl rollout undo deployment/astraos-app -n astraos

# 4. Or rollback to specific revision
kubectl rollout undo deployment/astraos-app -n astraos --to-revision=42

# 5. Verify health
kubectl get pods -n astraos -w
curl https://api.astraos.io/api/v1/health/readiness
```

---

### 7. Security Incident / Breach

**Symptoms**: Unusual access patterns, unauthorized data access, vulnerability exploitation  
**Impact**: Data breach, compliance violation  
**Runbook**:

```bash
# 1. IMMEDIATE: Isolate affected systems
kubectl scale deployment/astraos-app -n astraos --replicas=0

# 2. Revoke compromised credentials
# - Rotate APP_KEY
# - Rotate database passwords
# - Revoke API tokens
# - Rotate webhook secrets

# 3. Preserve evidence
# - Snapshot affected databases
# - Export relevant logs (audit_logs, nginx access logs)
# - Save Kubernetes events

# 4. Notify security team and legal
# Follow breach notification procedures (GDPR: 72 hours)

# 5. Deploy hotfix / WAF rules
# - Apply security patches
# - Enable WAF blocking rules
# - Update firewall rules

# 6. Gradual restoration with enhanced monitoring
kubectl scale deployment/astraos-app -n astraos --replicas=1
# Monitor closely before scaling up
```

---

## Communication Protocols

### Internal Communication

| Channel | Purpose | Audience |
|---------|---------|----------|
| **#incidents** (Slack) | Real-time incident coordination | Engineering, SRE, On-call |
| **#incidents-leadership** | Executive updates | VPE, CTO, CEO |
| **PagerDuty/OpsGenie** | Alerting & on-call paging | On-call engineer |
| **StatusPage.io** | Customer-facing status | All customers |

### Customer Communication (SEV-1/SEV-2)

1. **T+15min**: Post initial status on StatusPage ("Investigating")
2. **T+30min**: Update with known impact scope
3. **T+60min**: Provide workaround if available
4. **Every 30min**: Status update until resolved
5. **Resolution**: Post resolution summary, RCA timeline

### External Communication Template

```
Title: [SEV-1] Astra OS - API Outage - Investigating

Impact: API returning 503 errors for all customers
Started: 2026-08-03 14:22 UTC
Status: Investigating

We are aware of an issue affecting the Astra OS API. Our engineering team is investigating.
We will provide an update within 15 minutes.

Workaround: None currently available.
```

---

## Post-Incident Process

### 1. Blameless Post-Mortem (within 48 hours)

**Attendees**: On-call engineer, team lead, relevant stakeholders  
**Facilitator**: Engineering lead or SRE  
**Output**: Post-mortem document in Confluence/Notion

**Template**:
```markdown
# Post-Mortem: [Incident ID] - [Title]

## Summary
- **Date**: YYYY-MM-DD
- **Duration**: X hours Y minutes
- **Severity**: SEV-X
- **Impact**: [User-facing impact description]

## Timeline
- T+0: Alert fired / Customer report
- T+X: On-call acknowledged
- T+Y: Root cause identified
- T+Z: Fix deployed
- T+W: Service restored

## Root Cause
[5 Whys analysis]

## Contributing Factors
- Factor 1
- Factor 2

## Action Items
| ID | Action | Owner | Due Date | Status |
|----|--------|-------|----------|--------|
| PM-001 | Add alert for X | @engineer | YYYY-MM-DD | Open |
| PM-002 | Refactor Y to prevent Z | @team | YYYY-MM-DD | Open |

## What Went Well
- Quick detection via alert X
- Clear communication in #incidents

## What Could Be Improved
- Runbook for scenario Y was outdated
- Escalation to lead took 25 minutes
```

### 2. Action Item Tracking

- All action items tracked in Jira/Linear with `postmortem` label
- Monthly review of open post-mortem action items
- Quarterly trend analysis of incident categories

### 3. Runbook Updates

- Update relevant runbook within 1 week of post-mortem
- Add new runbook if scenario wasn't covered
- Review all runbooks quarterly

---

## On-Call Schedule

| Week | Primary | Secondary |
|------|---------|-----------|
| Week 1 | Engineer A | Engineer B |
| Week 2 | Engineer C | Engineer D |
| Week 3 | Engineer E | Engineer A |
| Week 4 | Engineer B | Engineer C |

**Rotation**: Weekly, Monday 9:00 AM UTC handoff  
**Handoff Checklist**:
- [ ] Review open incidents
- [ ] Check alert noise from past week
- [ ] Confirm pager duty schedule
- [ ] Verify access to all systems
- [ ] Update #incidents with contact info

---

## Key Dashboards & Links

| Dashboard | URL |
|-----------|-----|
| **Grafana - Astra OS Overview** | `https://grafana.astraos.io/d/astraos-overview` |
| **Horizon Queue Dashboard** | `https://api.astraos.io/horizon` |
| **Pulse Performance** | `https://api.astraos.io/pulse` |
| **Telescope Requests** | `https://api.astraos.io/telescope` |
| **Kubernetes** | `https://k8s.astraos.io` |
| **StatusPage** | `https://status.astraos.io` |

---

## Quick Reference Commands

```bash
# Health checks
curl https://api.astraos.io/api/v1/health
curl https://api.astraos.io/api/v1/health/readiness
curl https://api.astraos.io/api/v1/health/startup

# Pod status
kubectl get pods -n astraos -o wide

# Logs
kubectl logs -n astraos deploy/astraos-app --since=10m -f
kubectl logs -n astraos deploy/queue-worker --since=10m -f

# Scale
kubectl scale deployment/astraos-app -n astraos --replicas=5
kubectl scale deployment/queue-worker -n astraos --replicas=10

# Rollback
kubectl rollout undo deployment/astraos-app -n astraos

# Database
kubectl exec -n astraos deploy/astraos-app -- php artisan db:monitor
kubectl exec -n astraos deploy/astraos-app -- php artisan migrate:status

# Queue
kubectl exec -n astraos deploy/astraos-app -- php artisan horizon:status
kubectl exec -n astraos deploy/astraos-app -- php artisan queue:failed
```