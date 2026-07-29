<div align="center">
  <h1>✦ Astra OS Enterprise</h1>
  <p><strong>AI-Native Marketing & Business Growth Platform</strong></p>
  <p>
    <a href="#-overview">Overview</a> •
    <a href="#-architecture">Architecture</a> •
    <a href="#-quick-start">Quick Start</a> •
    <a href="#-api">API</a> •
    <a href="#-deployment">Deployment</a> •
    <a href="#-monitoring">Monitoring</a> •
    <a href="#-security">Security</a>
  </p>
  <p>
    <img src="https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel" alt="Laravel 13">
    <img src="https://img.shields.io/badge/PHP-8.4-777BB4?logo=php" alt="PHP 8.4">
    <img src="https://img.shields.io/badge/PostgreSQL-16-336791?logo=postgresql" alt="PostgreSQL 16">
    <img src="https://img.shields.io/badge/Redis-7-DC382D?logo=redis" alt="Redis 7">
    <img src="https://img.shields.io/badge/Clean_Architecture-DDD-blue" alt="Clean Architecture DDD">
    <img src="https://img.shields.io/badge/tests-272_pass-green" alt="272 tests">
    <img src="https://img.shields.io/badge/version-1.9.0-purple" alt="v1.9.0">
  </p>
</div>

---

## ✦ Overview

Astra OS Enterprise is a production-grade, Clean Architecture DDD application for orchestrating AI-powered marketing campaigns across multiple platforms. Built with **Laravel 13**, **PHP 8.4**, and **Domain-Driven Design** principles, it provides:

- **🎯 Campaign Management** — Multi-platform ad campaign creation, launch, optimization, and analytics
- **🤖 AI Agent Orchestration** — Deploy autonomous AI agents for content, analysis, bidding, and reporting
- **🔄 Workflow Automation** — Complex multi-step workflow engine with triggers, conditions, and branching
- **📊 Social Intelligence** — Unified social account management, content scheduling, and mention monitoring
- **📈 Analytics & Reporting** — Real-time campaign metrics, cross-platform performance comparison
- **🔌 Integrations** — Telegram bot, webhook system, notification channels, export/import

### Domain Modules

| Module | Description | Key Entities |
|--------|-------------|-------------|
| **Organization** | Multi-tenant org structure with member management | Organization, OrganizationMember |
| **Campaign** | Full campaign lifecycle across platforms | Campaign, Creative, Insight |
| **Agent** | Hierarchical AI agent system with memory | Agent, AgentTask, AgentMemory |
| **Workflow** | Visual workflow engine with triggers | Workflow, WorkflowExecution, WorkflowTemplate |
| **Social** | Unified social media management | SocialAccount, SocialPost, SocialMention |
| **Analytics** | Cross-platform performance analytics | CampaignAnalytics, Report |
| **Common** | Shared domain primitives | Value Objects (Email, Money, Address), Events |

## 🏗 Architecture

```
┌─────────────────────────────────────────────────────┐
│                   Presentation Layer                 │
│  Controllers · Middleware · Form Requests · Resources │
│  API (50+ endpoints) · Health Checks · Webhooks     │
├─────────────────────────────────────────────────────┤
│                  Application Layer                    │
│  Use Cases · DTOs · Services · Jobs · Notifications  │
├─────────────────────────────────────────────────────┤
│                 Infrastructure Layer                  │
│  Eloquent Models · Repositories · Migrations · Queue │
├─────────────────────────────────────────────────────┤
│                   Domain Layer                        │
│  Entities · Value Objects · Events · Repositories    │
│  (ZERO framework dependencies)                       │
└─────────────────────────────────────────────────────┘
```

### Technology Stack

- **Backend:** Laravel 13, PHP 8.4
- **Database:** PostgreSQL 16, Redis 7
- **Queue:** Laravel Horizon (Redis)
- **Auth:** Laravel Sanctum (API tokens)
- **Monitoring:** Laravel Pulse + Telescope
- **Caching:** Redis with tag-based invalidation
- **DevOps:** Docker, K8s, GitHub Actions CI/CD
- **AI:** OpenAI, Anthropic Claude integration (via AI agents)

## 🚀 Quick Start

### Prerequisites

- PHP 8.4+
- Composer 2.x
- PostgreSQL 16+
- Redis 7+
- Node.js 22+ (for frontend builds)

### Installation

```bash
# Clone the repository
git clone https://github.com/webbixray/astra-os-enterprise.git
cd astra-os-enterprise

# Install PHP dependencies
composer install

# Set up environment
cp .env.example .env
php artisan key:generate

# Configure database in .env
# DB_CONNECTION=pgsql
# DB_HOST=127.0.0.1
# DB_PORT=5432
# DB_DATABASE=astra_os
# DB_USERNAME=astra_os
# DB_PASSWORD=

# Run migrations and seeders
php artisan migrate --seed

# Start development server
php artisan serve

# In another terminal, start queue worker
php artisan horizon
```

### Docker (Development)

```bash
docker compose up -d
docker compose exec app php artisan migrate --seed
```

## 📡 API

Astra OS exposes **50+ RESTful endpoints** across 15 controllers.

### Authentication

```bash
# Register a new user
curl -X POST https://api.astra-os.com/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"John","email":"john@example.com","password":"secure-pass"}'

# Login
curl -X POST https://api.astra-os.com/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"john@example.com","password":"secure-pass"}'

# Response:
# {"data":{"token":"1|abc123...","user":{...}}}
```

### Core Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/v1/campaigns` | List campaigns |
| `POST` | `/api/v1/campaigns` | Create campaign |
| `GET` | `/api/v1/campaigns/{id}` | Get campaign |
| `PUT` | `/api/v1/campaigns/{id}` | Update campaign |
| `DELETE` | `/api/v1/campaigns/{id}` | Delete campaign |
| `POST` | `/api/v1/campaigns/{id}/launch` | Launch campaign |
| `POST` | `/api/v1/campaigns/{id}/pause` | Pause campaign |
| `GET` | `/api/v1/agents` | List AI agents |
| `POST` | `/api/v1/agents/{id}/assign-task` | Assign task to agent |
| `GET` | `/api/v1/workflows` | List workflows |
| `POST` | `/api/v1/workflows/{id}/execute` | Execute workflow |
| `GET` | `/api/v1/analytics/overview` | Analytics overview |
| `GET` | `/api/health` | System health |

### Response Format

All API responses follow a standardized format:

```json
{
  "success": true,
  "message": "Campaign created successfully",
  "data": { ... },
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 72
  }
}
```

Full API documentation is available at `/api/documentation` (Swagger UI).

## 📦 Deployment

### Production Docker

```bash
docker compose -f docker-compose.prod.yml up -d
```

### Manual Deployment

1. Configure production `.env` with secure credentials
2. Run `./deploy.sh production`
3. Set up SSL certificates (Let's Encrypt)
4. Configure monitoring alerting

### Kubernetes

```bash
kubectl apply -f k8s/production/
```

### System Requirements

| Resource | Minimum | Recommended |
|----------|---------|-------------|
| CPU | 2 cores | 4+ cores |
| RAM | 4 GB | 8+ GB |
| Storage | 20 GB SSD | 50+ GB SSD |
| Database | PostgreSQL 16 | PostgreSQL 16 + Redis 7 |

## 📊 Monitoring

| Tool | Path | Purpose |
|------|------|---------|
| **Pulse** | `/pulse` | Performance metrics, slow queries, request monitoring |
| **Horizon** | `/horizon` | Queue job monitoring, worker management |
| **Telescope** | `/telescope` | Debugging, request logging, exception tracking |
| **Health** | `/api/health` | Liveness/readiness/startup probes |
| **Logs** | `storage/logs/` | Structured JSON logs, daily rotation (90 days) |

## 🔒 Security

- **Authentication:** Sanctum token-based API auth
- **Authorization:** Organization-scoped access control
- **Headers:** Strict CSP, HSTS, X-Frame-Options, X-Content-Type-Options
- **Input:** SQL injection & XSS pattern blocking, input sanitization
- **Encryption:** Sensitive model field encryption at rest
- **Webhooks:** HMAC-SHA256 signature verification
- **Rate Limiting:** Per-endpoint rate limiting (60 req/min API, 10 req/min auth)
- **Session:** HTTP-only, Secure, SameSite=Strict cookies
- **Audit:** Comprehensive security event logging

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run specific suites
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# With coverage report
php artisan test --coverage
```

**Test Count:** 272 test methods across 24 test files (unit + feature + integration)

## 📁 Project Structure

```
astra-os-enterprise/
├── app/
│   ├── Console/Commands/       # Artisan commands
│   ├── Domain/                 # Domain layer (0 framework deps)
│   │   ├── Campaign/
│   │   ├── Agent/
│   │   ├── Workflow/
│   │   ├── Organization/
│   │   ├── Social/
│   │   ├── Common/
│   │   └── Analytics/
│   ├── Application/            # Application layer
│   │   ├── Campaign/
│   │   ├── Agent/
│   │   ├── Workflow/
│   │   ├── Organization/
│   │   └── Social/
│   ├── Infrastructure/         # Infrastructure layer
│   │   ├── Persistence/Models/
│   │   └── Persistence/Repositories/
│   ├── Http/                   # Presentation layer
│   │   ├── Controllers/
│   │   ├── Middleware/
│   │   ├── Requests/
│   │   └── Resources/
│   ├── Services/               # Domain services
│   │   ├── Telegram/
│   │   ├── Webhooks/
│   │   ├── Notifications/
│   │   ├── Export/
│   │   ├── Security/
│   │   └── Cache/
│   └── Providers/              # Service providers
├── config/                     # 18+ configuration files
├── database/
│   ├── migrations/             # 26 database migrations
│   ├── factories/              # Model factories
│   └── seeders/                # Database seeders
├── docker/                     # Docker configuration
├── k8s/                        # Kubernetes manifests
├── routes/                     # API, web, console, channels
├── tests/                      # 272 test methods
├── docs/                       # Documentation
├── deploy.sh                   # Deployment script
└── rollback.sh                 # Rollback script
```

## 🤝 Contributing

Please read [CONTRIBUTING.md](CONTRIBUTING.md) for details on our code of conduct and the process for submitting pull requests.

## 📄 License

This project is licensed under the MIT License — see [LICENSE](LICENSE) for details.

## 🏢 About

Built and maintained by the Astra OS Development Team as an enterprise-grade software product. Crafted with Clean Architecture DDD principles, designed for production scale.

---

<div align="center">
  <p>✦ Astra OS Enterprise v1.9.0</p>
</div>
