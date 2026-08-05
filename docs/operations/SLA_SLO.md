# Service Level Agreements & Objectives

> **Classification**: Internal - Operations & Product Teams
> **Last Updated**: August 2026
> **Version**: 1.0

---

## Overview

This document defines the Service Level Agreements (SLAs) for Astra OS customers and the internal Service Level Objectives (SLOs) that drive engineering priorities.

---

## Customer-Facing SLAs

### Availability SLA

| Tier | Uptime Target | Monthly Downtime | Annual Downtime | Credits |
|------|---------------|------------------|-----------------|---------|
| **Enterprise** | 99.99% | ≤ 4.38 min | ≤ 52.6 min | 10% per 0.1% below |
| **Professional** | 99.95% | ≤ 21.9 min | ≤ 4.38 hr | 5% per 0.1% below |
| **Starter** | 99.9% | ≤ 43.8 min | ≤ 8.76 hr | 2% per 0.1% below |

**Measurement**: 
- Excludes scheduled maintenance (max 4 hrs/month, announced 7 days prior)
- Measured at API gateway level (5xx errors / total requests)
- Minimum 1000 requests/month for SLA to apply

### Latency SLA (P95)

| Endpoint Category | Enterprise | Professional | Starter |
|-------------------|------------|--------------|---------|
| **Health/Readiness** | < 50ms | < 100ms | < 200ms |
| **Auth (login/register)** | < 500ms | < 1000ms | < 2000ms |
| **CRUD Operations** | < 300ms | < 500ms | < 1000ms |
| **Campaign Launch** | < 2000ms | < 5000ms | < 10000ms |
| **Analytics/Reports** | < 5000ms | < 10000ms | < 30000ms |
| **AI Agent Tasks** | < 30000ms | < 60000ms | < 120000ms |

### Throughput SLA

| Tier | Requests/minute | Burst Allowance |
|------|-----------------|-----------------|
| **Enterprise** | 10,000 | 50,000 |
| **Professional** | 2,000 | 10,000 |
| **Starter** | 500 | 2,000 |

### Data Durability SLA

| Metric | Target |
|--------|--------|
| **Database Durability** | 99.999999999% (11 9's) |
| **Backup Recovery (RPO)** | < 1 hour (Enterprise), < 4 hours (Pro), < 24 hours (Starter) |
| **Backup Recovery (RTO)** | < 4 hours (Enterprise), < 24 hours (Pro), < 72 hours (Starter) |

---

## Internal SLOs (Engineering Targets)

### Availability SLOs

| Service | Target | Measurement Window |
|---------|--------|-------------------|
| **API Gateway** | 99.99% | 30-day rolling |
| **Application (PHP-FPM)** | 99.95% | 30-day rolling |
| **Queue Workers** | 99.9% | 30-day rolling |
| **Scheduler** | 99.99% | 30-day rolling |
| **Database (Primary)** | 99.99% | 30-day rolling |
| **Redis** | 99.95% | 30-day rolling |

### Latency SLOs (P50 / P95 / P99)

| Endpoint | P50 | P95 | P99 |
|----------|-----|-----|-----|
| **Health** | 10ms / 50ms / 100ms | - | - |
| **Auth** | 100ms / 300ms / 500ms | - | - |
| **List Campaigns** | 50ms / 150ms / 300ms | - | - |
| **Create Campaign** | 100ms / 300ms / 500ms | - | - |
| **Campaign Analytics** | 500ms / 2000ms / 5000ms | - | - |
| **AI Agent Task** | 5000ms / 15000ms / 30000ms | - | - |

### Error Rate SLOs

| Metric | Target | Alert Threshold |
|--------|--------|-----------------|
| **HTTP 5xx Rate** | < 0.1% | > 0.5% for 5min |
| **HTTP 4xx Rate** | < 2% | > 10% for 5min |
| **Queue Job Failure Rate** | < 0.5% | > 2% for 10min |
| **Database Error Rate** | < 0.01% | > 0.1% for 5min |

### Queue SLOs

| Metric | Target |
|--------|--------|
| **Job Processing Latency (P95)** | < 30 seconds |
| **Queue Depth (max)** | < 10,000 pending |
| **Retry Rate** | < 5% of jobs |
| **Dead Letter Queue Size** | < 100 messages |

### Database SLOs

| Metric | Target |
|--------|--------|
| **Query Duration (P95)** | < 500ms |
| **Connection Pool Usage** | < 80% |
| **Replication Lag** | < 1 second |
| **Backup Success Rate** | 100% |
| **Backup Duration** | < 30 minutes |

---

## Error Budget Policy

### Budget Calculation

```
Error Budget = (1 - SLO Target) × Total Requests in Window
```

**Example**: 99.9% availability over 30 days = 43.2 minutes error budget

### Budget Consumption Actions

| Budget Remaining | Action |
|------------------|--------|
| **> 50%** | Normal feature development |
| **25-50%** | Prioritize reliability work, reduce deployment frequency |
| **10-25%** | Freeze non-critical deployments, focus on stability |
| **< 10%** | **All hands on reliability**, incident response mode |
| **0%** | Emergency: halt all changes, immediate reliability sprint |

### Budget Reset

- Resets at start of each calendar month
- Carryover: up to 10% unused budget rolls over
- Quarterly review of SLO targets

---

## Monitoring & Alerting

### SLO Alert Rules

| Alert | Condition | Severity | Notification |
|-------|-----------|----------|--------------|
| **Error Budget Burn Rate** | Burn rate > 14.4x (2% budget/hour) | SEV-1 | Page on-call |
| **Error Budget Burn Rate** | Burn rate > 6x (1% budget/hour) | SEV-2 | Slack + Email |
| **Error Budget Burn Rate** | Burn rate > 2x (0.25% budget/hour) | SEV-3 | Slack |
| **Latency SLO Breach** | P95 > threshold for 5min | SEV-2 | Slack |
| **Availability SLO Breach** | 5xx rate > 0.5% for 5min | SEV-1 | Page on-call |

### Dashboards

| Dashboard | Key Metrics |
|-----------|-------------|
| **SLO Dashboard** | Error budget remaining, burn rate, SLO compliance |
| **Latency Dashboard** | P50/P95/P99 by endpoint, trend |
| **Error Budget Dashboard** | Consumption rate, projected exhaustion |
| **Queue Dashboard** | Depth, processing time, failure rate |

---

## Reporting

### Monthly SLA Report (to customers)

- Availability % (actual vs target)
- Latency P50/P95/P99 (actual vs target)
- Incident summary (count, duration, root causes)
- Upcoming maintenance windows
- Credit calculations (if applicable)

### Weekly SLO Review (internal)

- Error budget status per service
- Top error contributors
- Deployment impact analysis
- Action items from incidents

### Quarterly SLO Review (engineering leadership)

- SLO target appropriateness
- Trend analysis (improving/degrading)
- Investment priorities
- Architecture changes impact

---

## Escalation Contacts

| Role | Name | Slack | PagerDuty | Email |
|------|------|-------|-----------|-------|
| **Primary On-Call** | Rotating | @oncall-primary | PagerDuty | oncall@astraos.io |
| **Secondary On-Call** | Rotating | @oncall-secondary | PagerDuty | oncall-secondary@astraos.io |
| **Engineering Lead** | [Lead Name] | @eng-lead | - | eng-lead@astraos.io |
| **VP Engineering** | [VP Name] | @vp-eng | - | vp-eng@astraos.io |
| **SRE Lead** | [SRE Lead] | @sre-lead | - | sre@astraos.io |

---

## Change History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | 2026-08-03 | Platform Team | Initial SLA/SLO definitions |