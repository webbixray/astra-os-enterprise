# Compliance & Governance Documentation

> **Classification**: Internal - Legal, Security, Engineering
> **Last Updated**: August 2026
> **Version**: 1.0

---

## Table of Contents

1. [Regulatory Landscape](#regulatory-landscape)
2. [GDPR Compliance](#gdpr-compliance)
3. [Data Processing Agreement](#data-processing-agreement)
4. [Security Certifications](#security-certifications)
5. [Audit Requirements](#audit-requirements)
6. [Data Retention Policies](#data-retention-policies)
7. [Third-Party Risk Management](#third-party-risk-management)
8. [Incident Response Compliance](#incident-response-compliance)

---

## Regulatory Landscape

| Regulation | Applicability | Status | Next Review |
|------------|---------------|--------|-------------|
| **GDPR** (EU 2016/679) | All EU customer data | 🟡 In Progress | Q1 2027 |
| **CCPA/CPRA** (California) | CA resident data | 🟡 In Progress | Q1 2027 |
| **SOC 2 Type II** | Security, Availability, Confidentiality | 🔴 Not Started | Q2 2027 |
| **ISO 27001** | Information Security Management | 🔴 Not Started | Q3 2027 |
| **HIPAA** | If processing PHI | 🟢 N/A (not processing PHI) | N/A |
| **PCI DSS** | If processing payments | 🟢 N/A (using Stripe/PayPal) | N/A |

---

## GDPR Compliance

### Lawful Basis for Processing

| Data Category | Lawful Basis | Retention |
|---------------|--------------|-----------|
| **Account Data** (name, email, org) | Contract (Art 6.1.b) | Account lifetime + 30 days |
| **Campaign Data** | Legitimate Interest (Art 6.1.f) | 7 years (business records) |
| **Analytics/Tracking** | Consent (Art 6.1.a) | 26 months |
| **AI Agent Interactions** | Legitimate Interest | 90 days |
| **Audit Logs** | Legal Obligation (Art 6.1.c) | 7 years |
| **Payment Data** | Contract | 7 years (via Stripe) |

### Data Subject Rights Implementation

| Right | Implementation | API Endpoint | SLA |
|-------|----------------|--------------|-----|
| **Access (Art 15)** | Export all user data | `GET /api/v1/gdpr/export` | 30 days |
| **Rectification (Art 16)** | Update profile/org data | `PUT /api/v1/auth/me` | Immediate |
| **Erasure (Art 17)** | Delete account + data | `DELETE /api/v1/gdpr/erase` | 30 days |
| **Restriction (Art 18)** | Suspend processing | `POST /api/v1/gdpr/restrict` | Immediate |
| **Portability (Art 20)** | Machine-readable export | `GET /api/v1/gdpr/export` | 30 days |
| **Objection (Art 21)** | Opt-out of analytics | `POST /api/v1/gdpr/opt-out` | Immediate |

### Technical Measures

```php
// Data minimization - only collect necessary fields
// Purpose limitation - explicit consent per feature
// Storage limitation - automated cleanup jobs
// Integrity & confidentiality - encryption at rest + TLS 1.3

// Pseudonymization for analytics
User::where('gdpr_restricted', true)
    ->update(['email' => 'hash_' . hash('sha256', $email)]);
```

### Data Protection Impact Assessment (DPIA)

**Required for**:
- ✅ AI agent automated decision-making
- ✅ Large-scale profiling (campaign optimization)
- ✅ Cross-border data transfers (US AI providers)

**Status**: DPIA completed for AI agents (August 2026)  
**Review**: Annual or on significant architecture changes

---

## Data Processing Agreement (DPA)

### Controller-Processor Relationship

| Role | Party | Responsibilities |
|------|-------|------------------|
| **Controller** | Customer (Marketing Team) | Determines purposes, lawful basis |
| **Processor** | Astra OS | Processes on behalf, security measures |
| **Sub-processors** | OpenAI, Anthropic, Meta, Google, AWS | Listed in DPA Schedule 1 |

### Sub-processor Management

| Sub-processor | Purpose | Location | Safeguards |
|---------------|---------|----------|------------|
| **OpenAI** | AI agent LLM | US | SCCs, DPF |
| **Anthropic** | AI agent LLM | US | SCCs, DPF |
| **AWS** | Hosting, RDS, ElastiCache | EU/US | SCCs, DPF |
| **Redis Labs** | Managed Redis | EU/US | SCCs |
| **SendGrid/Resend** | Email delivery | US | SCCs, DPF |
| **Twilio** | SMS/Telegram | US | SCCs |

### DPA Terms

- **Written Agreement**: DPA incorporated in MSA/Terms of Service
- **Security**: AES-256 at rest, TLS 1.3 in transit
- **Breach Notification**: 24 hours to Controller
- **Deletion**: Within 30 days of contract termination
- **Audit Rights**: Controller may audit annually
- **International Transfers**: SCCs + DPF where applicable

---

## Security Certifications

### Current Status

| Certification | Status | Target Date | Scope |
|---------------|--------|-------------|-------|
| **SOC 2 Type I** | 🔴 Not Started | Q1 2027 | Security, Availability |
| **SOC 2 Type II** | 🔴 Not Started | Q3 2027 | Security, Availability, Confidentiality |
| **ISO 27001** | 🔴 Not Started | Q4 2027 | ISMS |
| **Cyber Essentials Plus** | 🔴 Not Started | Q4 2026 | Basic hygiene |

### Certification Readiness Checklist

#### SOC 2 Common Criteria (CC)

| Criterion | Implementation | Evidence |
|-----------|----------------|----------|
| **CC1.1** - Control Environment | Org structure, policies | Org chart, employee handbook |
| **CC2.1** - Communication | Internal/external comms | Slack, statuspage, runbooks |
| **CC3.1** - Risk Assessment | Annual risk assessment | Risk register, DPIA |
| **CC4.1** - Monitoring | Prometheus, Sentry, Pulse | Dashboards, alerts |
| **CC5.1** - Control Activities | Access control, encryption | Policies, config audits |
| **CC6.1** - Logical Access | RBAC, MFA, least privilege | IAM policies, audit logs |
| **CC7.1** - System Operations | Change management, backups | CI/CD, deploy scripts |
| **CC8.1** - Change Management | PR review, testing, deploy | GitHub branch protection |
| **CC9.1** - Risk Mitigation | Vendor management, BCP | Vendor register, DRP |

#### SOC 2 Additional Criteria (A - Availability)

| Criterion | Implementation | Evidence |
|-----------|----------------|----------|
| **A1.1** - Availability Monitoring | Health checks, HPA | K8s probes, Grafana |
| **A1.2** - Incident Response | Runbooks, on-call | INCIDENT_RESPONSE.md |
| **A1.3** - Disaster Recovery | Backups, RTO/RDR | backup.sh, DRP doc |

---

## Audit Requirements

### Internal Audits

| Audit Type | Frequency | Owner | Scope |
|------------|-----------|-------|-------|
| **Access Review** | Quarterly | Engineering Lead | User roles, API keys, DB access |
| **Code Security** | Per Release | Security Team | SAST, dependency scan, secrets |
| **Infrastructure** | Monthly | SRE | K8s config, network policies, encryption |
| **Data Privacy** | Semi-annual | DPO | DPIA review, consent records, DSR logs |

### External Audits

| Audit | Frequency | Auditor | Deliverable |
|-------|-----------|---------|-------------|
| **Penetration Test** | Annual | 3rd Party | Pentest report + remediation |
| **SOC 2** | Annual | CPA Firm | SOC 2 Type II Report |
| **ISO 27001** | Annual | Registrar | ISO 27001 Certificate |
| **Vulnerability Scan** | Quarterly | Automated + Manual | Scan report |

### Audit Evidence Collection

Automated via:
- **GitHub**: PR reviews, branch protection, dependabot
- **CI/CD**: Test results, security scans, deploy logs
- **Kubernetes**: Audit logs, policy violations
- **Application**: Audit logs (spatie/laravel-activitylog)
- **Monitoring**: Alert history, uptime reports

---

## Data Retention Policies

| Data Category | Retention Period | Deletion Method | Legal Basis |
|---------------|------------------|-----------------|-------------|
| **User Accounts** | Account lifetime + 30 days | Soft delete → Hard delete after 30d | GDPR Art 17 |
| **Campaigns** | 7 years | Archive → Delete | Business records |
| **Analytics Events** | 26 months | Partition drop | GDPR consent |
| **AI Agent Logs** | 90 days | TTL auto-delete | Legitimate interest |
| **Audit Logs** | 7 years | Immutable storage | Legal obligation |
| **Payment Records** | 7 years | Via Stripe (PCI) | Tax/Accounting |
| **Emails/Notifications** | 2 years | Auto-delete | Business records |
| **Backups** | Daily: 7d, Weekly: 4w, Monthly: 12m, Yearly: 2y | S3 lifecycle | Business continuity |
| **Access Logs** | 90 days | Rotation | Security monitoring |

### Automated Retention Enforcement

```bash
# Laravel scheduled commands (run daily)
php artisan gdpr:cleanup-expired-exports
php artisan analytics:prune-old-events
php artisan audit:cleanup-expired-logs
php artisan ai-agent:prune-old-memories
php artisan backup:clean
```

---

## Third-Party Risk Management

### Vendor Assessment Framework

| Risk Tier | Criteria | Assessment Frequency | Controls |
|-----------|----------|---------------------|----------|
| **Critical** | Access to customer data, core infrastructure | Annual + Continuous | SOC 2, penetration test, contractual SLA |
| **High** | Access to systems, significant integration | Annual | Security questionnaire, certifications |
| **Medium** | Limited data access, replaceable | Biennial | Security questionnaire |
| **Low** | No data access, commodity services | As needed | Basic due diligence |

### Current Vendor Register

| Vendor | Tier | Data Access | Certifications | Contract Renewal |
|--------|------|-------------|----------------|------------------|
| **AWS** | Critical | Full (hosting) | SOC 2, ISO 27001, PCI | Annual |
| **OpenAI** | Critical | AI prompts/responses | SOC 2 Type II | Monthly |
| **Anthropic** | Critical | AI prompts/responses | SOC 2 Type II | Monthly |
| **PostgreSQL (RDS)** | Critical | Customer DB | AWS compliance | N/A |
| **Redis (ElastiCache)** | Critical | Cache/sessions | AWS compliance | N/A |
| **SendGrid** | High | Email content | SOC 2, ISO 27001 | Annual |
| **Cloudflare** | High | Traffic/WAF | SOC 2, ISO 27001 | Annual |
| **GitHub** | High | Source code | SOC 2, ISO 27001 | Annual |
| **Sentry** | Medium | Error traces | SOC 2 | Annual |
| **Slack** | Low | Notifications | SOC 2, ISO 27001 | Annual |

### Vendor Onboarding Process

1. **Security Questionnaire** (SIG Lite or custom)
2. **Certification Review** (SOC 2, ISO, PCI)
3. **Data Processing Agreement** execution
4. **Contractual SLA** with security clauses
5. **Risk Register** entry with owner
6. **Annual Re-assessment** calendar invite

---

## Incident Response Compliance

### GDPR Breach Notification (Art 33/34)

| Timeline | Action | Responsible |
|----------|--------|-------------|
| **T+0** | Detect/Confirm breach | On-call Engineer |
| **T+1hr** | Assess severity, data categories | Security Lead |
| **T+24hr** | **Notify Supervisory Authority** (if risk to rights) | DPO / Legal |
| **T+48hr** | **Notify Data Subjects** (if high risk) | DPO / Legal |
| **T+72hr** | Complete Art 33 notification form | DPO |
| **Ongoing** | Investigation, remediation, documentation | Security Team |

### Notification Template (Supervisory Authority)

```
Controller: Astra OS Enterprise
DPO: dpo@astraos.io
Breach Date: YYYY-MM-DD HH:MM UTC
Discovery Date: YYYY-MM-DD HH:MM UTC
Categories: [Personal data categories affected]
Records: [Approximate number]
Consequences: [Likely consequences]
Measures: [Taken/planned measures]
Contact: [DPO contact details]
```

### Compliance Incident Types

| Incident Type | Regulation | Notification Deadline |
|---------------|------------|----------------------|
| **Personal Data Breach** | GDPR Art 33 | 72 hours |
| **Security Breach** | CCPA 1798.150 | "Expedient" (typically 72h) |
| **Payment Data Breach** | PCI DSS | 24 hours (to brands) |
| **System Availability** | SOC 2 / Contract | Per SLA |

---

## Compliance Monitoring Dashboard

### Key Metrics

| Metric | Target | Current | Trend |
|--------|--------|---------|-------|
| **DSR Response Time** | < 30 days | - | - |
| **Consent Capture Rate** | 100% | - | - |
| **Data Encryption Coverage** | 100% | - | - |
| **Access Review Completion** | 100% quarterly | - | - |
| **Vendor Assessment Completion** | 100% annual | - | - |
| **Breach Notification Compliance** | 100% within deadline | - | - |
| **Audit Finding Remediation** | < 30 days (High) | - | - |
| **Training Completion** | 100% annual | - | - |

---

## Training Requirements

| Role | Training | Frequency | Tracking |
|------|----------|-----------|----------|
| **All Engineers** | Secure Coding (OWASP) | Annual | LMS |
| **All Engineers** | GDPR/Privacy Basics | Annual | LMS |
| **On-Call** | Incident Response | Quarterly | Runbook drills |
| **DPO/Legal** | Advanced Privacy Law | Annual | External |
| **Security Team** | Threat Modeling | Semi-annual | Internal |

---

## Compliance Roadmap

| Quarter | Milestone | Owner |
|---------|-----------|-------|
| **Q3 2026** | Complete GDPR DSR automation | Engineering |
| **Q4 2026** | SOC 2 Type I readiness assessment | Security |
| **Q1 2027** | SOC 2 Type I audit | Security + External |
| **Q2 2027** | SOC 2 Type II observation period | Security |
| **Q3 2027** | SOC 2 Type II audit | Security + External |
| **Q4 2027** | ISO 27001 Stage 1 audit | Security + External |
| **Q1 2028** | ISO 27001 Certification | Security + External |

---

## Document Control

| Version | Date | Author | Changes | Approved By |
|---------|------|--------|---------|-------------|
| 1.0 | 2026-08-03 | Platform Team | Initial compliance framework | [Pending] |