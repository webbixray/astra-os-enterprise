<div align="center">

# 🚀 Astra OS

**Enterprise AI-Powered Marketing Platform**

[![CI](https://github.com/astraos/astraos/actions/workflows/ci.yml/badge.svg)](https://github.com/astraos/astraos/actions/workflows/ci.yml)
[![PHP Version](https://img.shields.io/badge/php-8.4-%23777BB4?logo=php)](https://php.net/)
[![Laravel Version](https://img.shields.io/badge/laravel-13-%23FF2D20?logo=laravel)](https://laravel.com/)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![Docker](https://img.shields.io/badge/docker-ready-%232496ED?logo=docker)](https://docker.com/)
[![Kubernetes](https://img.shields.io/badge/k8s-ready-%23326CE5?logo=kubernetes)](https://kubernetes.io/)

</div>

---

## 📋 Overview

Astra OS is an enterprise-grade, AI-powered marketing platform that revolutionizes campaign management through intelligent automation. It combines multi-platform advertising management with AI agent orchestration, workflow automation, and real-time analytics.

### Key Features

- **🤖 AI Agent System** - Hierarchical AI agents (CEO, Director, Specialist) that collaborate on campaign management
- **📊 Campaign Management** - Multi-platform ad campaign creation, optimization, and monitoring
- **🔄 Workflow Automation** - Visual workflow builder for automating marketing processes
- **📱 Social Media Management** - Cross-platform social posting, monitoring, and engagement
- **📈 Analytics & Reporting** - Real-time performance analytics and automated report generation
- **🔒 Enterprise Security** - Role-based access control, audit logging, and encryption

### Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     API Gateway (Nginx)                      │
├─────────────────────────────────────────────────────────────┤
│                    Laravel 13 Application                    │
├──────────┬──────────┬──────────┬──────────┬─────────────────┤
│ Campaign │  Agents  │Workflows │  Social  │   Analytics     │
│ Manager  │  System  │ Engine   │  Module  │   Engine        │
├──────────┴──────────┴──────────┴──────────┴─────────────────┤
│                    Infrastructure Layer                      │
├─────────────┬──────────────┬──────────────┬─────────────────┤
│  PostgreSQL │    Redis     │     Queue    │      S3         │
│   (Data)    │   (Cache)    │  (Jobs)      │   (Storage)     │
└─────────────┴──────────────┴──────────────┴─────────────────┘
```

## 🚀 Quick Start

### Prerequisites

- PHP 8.4+
- Composer 2.x
- PostgreSQL 16+
- Redis 7+
- Node.js 20+ & NPM (for frontend assets)

### Local Development

```bash
# Clone the repository
git clone https://github.com/astraos/astraos.git
cd astraos

# Install dependencies
composer install

# Setup environment
cp .env.example .env
php artisan key:generate

# Setup database
php artisan migrate
php artisan db:seed

# Start development server
php artisan serve

# In another terminal, start the queue worker
php artisan queue:work
```

### Docker Quick Start

```bash
# Start all services
docker compose up -d

# Run migrations and seeders
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed

# Access the application
open http://localhost:8080
```

## 🐳 Docker Setup

```bash
# Development
docker compose -f docker/docker-compose.yml up -d

# Production (with resource limits)
docker compose -f docker/docker-compose.yml -f docker/docker-compose.prod.yml up -d
```

Services:
- **app** - PHP-FPM 8.4 application server
- **web** - Nginx 1.27 web server
- **db** - PostgreSQL 16 database
- **cache** - Redis 7 cache & session store
- **queue** - Queue worker process
- **scheduler** - Cron scheduler process
- **mailpit** - Email testing UI

## ☸️ Kubernetes Deployment

```bash
# Deploy to Kubernetes
kubectl apply -k k8s/base

# Production overlay
kubectl apply -k k8s/overlays/production

# Staging overlay
kubectl apply -k k8s/overlays/staging
```

## 🔧 Configuration

Key configuration files are published via the `AstraOsServiceProvider`:

| Config File | Description |
|------------|-------------|
| `config/astra-os/general.php` | Application-wide settings |
| `config/astra-os/features.php` | Feature flags |
| `config/agents/providers.php` | AI provider configurations |
| `config/agents/roles.php` | Agent role definitions |
| `config/campaigns/platforms.php` | Platform-specific configs |
| `config/campaigns/defaults.php` | Campaign defaults |
| `config/workflows/nodes.php` | Workflow node types |
| `config/workflows/templates.php` | Workflow template presets |

## 📡 API Reference

Full API documentation is available at `/docs/api/REFERENCE.md`.

### Health Check

```bash
curl http://localhost/api/v1/health
```

### Authentication

```bash
# Login
curl -X POST http://localhost/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@astraos.io","password":"password"}'
```

### Campaign Management

```bash
# List campaigns
curl -H "Authorization: Bearer {token}" \
  http://localhost/api/v1/organizations/{org}/campaigns
```

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run with coverage
XDEBUG_MODE=coverage php artisan test --coverage

# Run specific test suite
php artisan test --testsuite=Feature

# Run tests in parallel
php artisan test --parallel
```

## 🛠️ Commands

| Command | Description |
|---------|-------------|
| `astra-os:setup` | Full application setup |
| `astra-os:agents:process-tasks` | Process pending agent tasks |
| `astra-os:agents:prune-memory` | Clean up old agent memories |
| `astra-os:campaigns:sync` | Sync with ad platforms |
| `astra-os:campaigns:generate-reports` | Generate scheduled reports |
| `astra-os:social:monitor-mentions` | Check new social mentions |
| `astra-os:social:publish-scheduled` | Publish scheduled posts |
| `astra-os:maintenance:cleanup-audit-logs` | Prune audit logs |

## 🔐 Security

- All sensitive data encrypted at rest
- API authentication via Laravel Sanctum tokens
- Role-based access control (owner, admin, member)
- Comprehensive audit logging
- CSRF protection on web routes
- SQL injection prevention via Eloquent ORM
- XSS protection via Blade templating

## 📚 Documentation

- [Architecture Overview](docs/architecture/OVERVIEW.md)
- [Domain Model](docs/architecture/DOMAIN_MODEL.md)
- [API Reference](docs/api/REFERENCE.md)
- [Deployment Guide](docs/deployment/QUICKSTART.md)
- [Production Guide](docs/deployment/PRODUCTION.md)
- [Docker Setup](docs/deployment/DOCKER.md)

## 🤝 Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## 📄 License

Astra OS is open-source software licensed under the [MIT license](LICENSE).

## 🙏 Acknowledgments

- [Laravel](https://laravel.com/) - The PHP framework
- [PostgreSQL](https://postgresql.org/) - Database
- [Redis](https://redis.io/) - Cache & queue
- [Nginx](https://nginx.org/) - Web server
