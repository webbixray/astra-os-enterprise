# Documentation Index

> **Astra OS Enterprise Platform - Documentation Portal**

---

## Quick Navigation

### 🏗️ Architecture & Design
| Document | Description |
|----------|-------------|
| [`architecture/OVERVIEW.md`](architecture/OVERVIEW.md) | System context, container, component diagrams (C4 style) |
| [`architecture/DOMAIN_MODEL.md`](architecture/DOMAIN_MODEL.md) | Domain-driven design model, entities, value objects, aggregates |

### 🚀 Deployment & Operations
| Document | Description |
|----------|-------------|
| [`deployment/PRODUCTION.md`](deployment/PRODUCTION.md) | Production deployment guide (K8s, Docker, Terraform) |
| [`deployment/QUICKSTART.md`](deployment/QUICKSTART.md) | Local development quickstart |
| [`deployment/DOCKER.md`](deployment/DOCKER.md) | Docker Compose for local/dev environments |
| [`operations/OPERATIONS_MANUAL.md`](operations/OPERATIONS_MANUAL.md) | Daily/weekly/monthly ops, deploy, DB, queue, cache |
| [`operations/SLA_SLO.md`](operations/SLA_SLO.md) | Customer SLAs, internal SLOs, error budgets, reporting |

### 📋 Runbooks
| Document | Description |
|----------|-------------|
| [`runbooks/INCIDENT_RESPONSE.md`](runbooks/INCIDENT_RESPONSE.md) | SEV-1 to SEV-4 procedures, escalation, post-mortem |

### 🔒 Compliance & Governance
| Document | Description |
|----------|-------------|
| [`compliance/COMPLIANCE.md`](compliance/COMPLIANCE.md) | GDPR, SOC 2, ISO 27001, vendor risk, audit requirements |

### 📚 API Reference
| Document | Description |
|----------|-------------|
| [`api/REFERENCE.md`](api/REFERENCE.md) | Complete API v1 reference (auth, campaigns, agents, workflows, social) |

---

## Documentation Standards

### Format
- **Markdown** with Mermaid diagrams for architecture
- **Tables** for structured data (API endpoints, configs, SLAs)
- **Code blocks** for commands, configs, examples
- **Cross-references** using relative links

### Maintenance
- Update with every feature/architecture change
- Version-controlled in Git alongside code
- Reviewed quarterly for accuracy

---

## Diagrams

Architecture diagrams use **Mermaid.js** syntax. Render in:
- GitHub/GitLab markdown preview
- VS Code with Mermaid extension
- Mermaid Live Editor (https://mermaid.live)
- Documentation sites (Docusaurus, MkDocs, GitBook)

---

## Contributing to Documentation

1. **Edit** the relevant `.md` file
2. **Validate** links and formatting
3. **Preview** Mermaid diagrams
4. **Submit** PR with clear description

---

## Quick Links

| Resource | URL |
|----------|-----|
| **GitHub Repository** | `https://github.com/astraos/enterprise` |
| **API Base URL** | `https://api.astraos.io/api/v1` |
| **Status Page** | `https://status.astraos.io` |
| **Grafana Dashboards** | `https://grafana.astraos.io` |
| **Kubernetes Dashboard** | `https://k8s.astraos.io` |

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 2.1.0 | 2026-08-03 | Added runbooks, SLA/SLO, operations manual, compliance |
| 2.0.0 | 2026-07-30 | Initial production documentation |