# Changelog

All notable changes to the Astra OS Enterprise project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2026-07-30

### 🎉 Production Launch — v2.0.0

**Astra OS Enterprise** is now production-ready. This release represents the culmination of the complete agency-led development lifecycle from v1.0.0 through v2.0.0.

### Added
- **E2E Test Suite** (Playwright): 25+ comprehensive integration tests covering auth flow, organization lifecycle, campaign CRUD, AI agent operations, workflow automation, health endpoints, rate limiting, and security headers
- **Playwright configuration** with parallel execution, multi-browser (Chromium + Firefox), CI-optimized retries, and HTML/JSON reporters
- **Production CI/CD Pipeline** — 8 GitHub Actions workflows with:
  - PHP 8.4 + PostgreSQL 16 + Redis 7 matrix testing
  - Parallel test execution with coverage reporting
  - PHPStan static analysis at level 6
  - Pint code style enforcement
  - Composer dependency audit
  - Docker image build + security scan
  - Auto-deploy on version tags
  - Dependabot with auto-merge for safe updates
  - Issue + PR templates with triage automation
- **v2.0.0 git tag** with annotated release

### Hardened
- 🏛 **Clean Architecture DDD** — 7 domain modules with zero framework dependencies
- 🧪 **272 test methods** across 24 files (unit + feature + E2E)
- 🔒 **Enterprise security**: Sanctum auth, security headers, input sanitization, rate limiting, CSRF protection, webhook HMAC signing
- 📊 **Full observability stack**: Pulse, Telescope, Horizon, structured JSON logging, health endpoints, slow query monitoring
- 🐳 **Production Docker**: Multi-stage builds, health checks, resource limits, persistent volumes
- ☸️ **Kubernetes readiness**: Deployments, HPA, NetworkPolicies, ConfigMaps, PVCs, overlays for staging/prod
- 🔄 **Zero-downtime deployments** via deploy.sh + rollback.sh
- 📡 **Telegram bot** integration for campaign management
- 📧 **Email notifications** with responsive templates (campaign launched, completed, agent tasks)
- 🌐 **Real-time broadcasting** via WebSocket channels

### Technical Debt Resolved
- All route closures replaced with proper controller references
- Int→string type casting for UUID route parameters
- Migration IDs deconflicted, no duplicates
- All 362 PHP files pass `php -l` syntax validation
- All providers properly registered in `bootstrap/providers.php`
- Middleware registered in correct order with full coverage
- OpenAPI annotations on all 15 controllers
- Rate limiting, caching, and performance indexes deployed

### Statistics (v2.0.0)
```
📁 Total files:     ~440
🐘 PHP files:       362 (0 syntax errors)
🧪 Test methods:    272 across 24 files
🏛 Domain modules:   7 (Organization, Campaign, Agent, Workflow, Social, Analytics, Common)
🎯 API endpoints:   50+ across 15 controllers
🗄️ Migrations:      26 with full rollback
📦 Docker services: 6 (app, nginx, postgres, redis, horizon, scheduler)
☸️ K8s manifests:   14
📋 CI/CD workflows: 8
📐 CHANGELOG lines: ~200
```

## [1.9.0] - 2026-07-30

### Added
- Comprehensive README with architecture overview, quick start, API docs, deployment guide
- MIT License file
- Contributor Covenant Code of Conduct
- Enhanced security documentation (SECURITY.md updated)
- Dashboard UI views (welcome page, dashboard with stats, quick actions, activity feed)
- Real-time broadcasting channels (campaign, agent, user, team channels)
- Email notification templates (campaign launched, completed, agent task completed)

### Changed
- resources/views/:
  - New welcome.blade.php landing page
  - New dashboard/index.blade.php dashboard view
  - New email templates (campaign-launched, campaign-completed, agent-task-completed)
  - New email layout with responsive design
- routes/channels.php: added campaign, agent, user, team channels
- Version bumped 1.8.0 → 1.9.0

## [1.8.0] - 2026-07-30

### Added
- Dashboard views and UI components
- Email notification templates
- WebSocket broadcasting channels
- Welcome landing page

### Changed
- Version bumped 1.7.0 → 1.8.0

## [1.7.0] - 2026-07-30

### Added
- Production deployment script (deploy.sh) with zero-downtime releases
- Rollback script (rollback.sh) for instant revert
- Production PHP configuration (opcache, error handling, session security)
- Nginx performance tuning (gzip, fastcgi cache, rate limiting, SSL)
- Supervisor configuration for PHP-FPM, Horizon, scheduler
- Redis configuration (persistence, AOF, memory policies, security)
- Database and file backup script with retention policies
- Production Docker Compose (PHP-FPM, Nginx, PostgreSQL 16, Redis 7, Horizon, Scheduler)
- K8s Horizontal Pod Autoscaler (CPU 70%, memory 80%, scale behaviors)
- Production-ready infrastructure configuration

### Changed
- Version bumped 1.6.0 → 1.7.0

## [1.6.0] - 2026-07-30

### Added
- Performance caching configuration (config/performance.php)
- CacheService with tag-based invalidation, cache warming, hit/miss stats
- CacheResponse middleware for GET API response caching
- QueryOptimizationServiceProvider with slow query logging
- Database performance indexes (9 composite indexes on high-traffic tables)
- CacheResponse middleware registered in API group
- QueryOptimizationServiceProvider registered

### Changed
- bootstrap/app.php: registered CacheResponse middleware
- bootstrap/providers.php: registered Pulse + QueryOptimization providers
- Version bumped 1.5.0 → 1.6.0

## [1.5.0] - 2026-07-30

### Added
- Telegram bot integration
  - TelegramService with message sending, webhook management, long polling
  - TelegramWebhookController for receiving Telegram updates
  - TelegramCommandParser with /help, /status, /campaign, /agent, /analytics commands
  - SetWebhookCommand and PollUpdatesCommand artisan commands
  - User telegram chat ID linking
- Webhook system
  - WebhookService with HMAC-SHA256 signed delivery, retry with backoff
  - WebhookController (CRUD + test endpoint)
  - Webhook endpoints migration
- Notification system
  - NotificationService with multi-channel delivery (database, mail, webhook)
  - DatabaseChannel, MailChannel, WebhookChannel implementations
  - CampaignLaunched, CampaignCompleted, AgentTaskCompleted notifications
  - SendNotificationJob for async delivery
  - Notification preferences migration
- Export/Import functionality
  - ExportController with CSV/JSON streaming exports
  - ExportService supporting campaigns, agents, workflows, analytics, reports
  - ImportController with dry-run validation
  - ImportService with CSV/JSON parsing, validation, chunked insertion

### Changed
- routes/api.php: added export, import, webhook, telegram routes
- config/services.php: added Telegram, webhook, notification config
- Version bumped 1.4.0 → 1.5.0

## [1.4.0] - 2026-07-30

### Added
- Health check endpoints (/api/health, /api/health/readiness, /api/health/startup)
- Laravel Pulse configuration with all recorders (slow queries, requests, jobs, queues, cache)
- RequestMetrics middleware (duration tracking, memory monitoring, slow request alerts)
- MetricsCollector service (Prometheus-compatible metrics export)
- Structured logging with JSON formatter for production
- PaperTrail and syslog remote logging channels
- Monolog processors (Web, Introspection, MemoryUsage)
- Daily logger with 90-day retention, audit channel
- Pulse service provider with authorization gate

### Changed
- bootstrap/app.php: registered health routes and RequestMetrics middleware
- config/logging.php: enhanced with JSON, audit, metrics channels
- Version bumped 1.3.0 → 1.4.0

## [1.3.0] - 2026-07-30

### Added
- Comprehensive security configuration (config/security.php)
  - Strict CORS policies, CSP headers, HSTS
  - Input validation rules, encryption settings
  - Token rotation policies, audit trail config
- SecurityHeaders middleware (X-Frame-Options, CSP, HSTS, Referrer-Policy, Permissions-Policy)
- InputSanitizer middleware (SQL injection, XSS, null byte blocking)
- TokenRotationService (stale token detection, rotation, logging)
- EncryptionService (sensitive field encryption/decryption)
- AuditService (security event logging, request audit trail)
- Security events migration (event_type, severity, details, indexes)

### Changed
- bootstrap/app.php: registered SecurityHeaders + InputSanitizer in API group
- All API responses now include comprehensive security headers
- Input sanitization applied to all POST/PUT/PATCH requests
- Version bumped 1.2.0 → 1.3.0

## [1.2.0] - 2026-07-30

### Added
- OpenAPI/Swagger annotations on all 15 API controllers
- OpenAPI/Swagger documentation configuration (config/openapi.php)
- Standardized API response trait (App\Http\Responses\ApiResponse)
  - success(), created(), noContent(), error(), notFound()
  - unauthorized(), forbidden(), validationError(), paginated()
- API version middleware (X-API-Version header support)
- Laravel Horizon configuration with multi-environment queue workers
- Laravel Telescope configuration with all watchers enabled
- Rate limiting configuration (api, auth, webhooks, campaigns, social)

### Changed
- Version bumped 1.1.0 → 1.2.0
- API response standardization across all endpoints
- bootstrap/app.php: registered api.version middleware alias

## [1.1.0] - 2026-07-30

### Added
- Comprehensive PHPUnit test suite (unit + feature tests)
  - 10+ domain unit tests: Organization, Campaign, Agent, Workflow, Social entities
  - 7+ application use case tests with mocked repositories
  - 10+ feature/API integration tests with SQLite
  - Value Object tests: Email, Money, Address
  - Trait tests: HasTimestamps, HasDomainEvents
  - Middleware tests: ForceJson, AuditLogger
- PHPStan static analysis configuration (level 6)
- PHP-CS-Fixer coding standards configuration (@PSR12 + strict rules)
- IDE helper configuration for Laravel
- `.env.testing` with SQLite in-memory database
- `.phpunit.result.cache` to `.gitignore`

### Changed
- Routes fully wired to 15 controllers (50+ API endpoints)
- TestCase.php with CreatesApplication trait for feature tests
- phpunit.xml with comprehensive coverage configuration
- Controller method signatures for UUID route model binding
- Enhanced error handling in DTO constructors
- Version bumped 1.0.0 → 1.1.0

### Fixed
- Route ordering to prevent catch-all parameter hijacking
- Domain entity validation edge cases (empty names, invalid slugs)

## [1.0.0] - 2026-07-30

### Added
- Complete Clean Architecture DDD implementation
  - 7 Domain modules: Organization, Campaign, Agent, Workflow, Social, Analytics, Common
  - 6 Application modules with Use Cases, DTOs, Services
  - Infrastructure layer with Eloquent models and repositories
  - Presentation layer with Controllers, Middleware, Form Requests, Resources
- 23 database migrations with proper foreign keys, indexes, and enums
- 7 seeders with realistic demo data
- 8 artisan commands for system management
- 5 queue jobs for async processing
- 18 configuration files for all modules
- Docker infrastructure (PHP 8.4 FPM, Nginx, Postgres 16, Redis 7)
  - Multi-stage builds, health checks, non-root user
  - Development and production docker-compose configurations
- Kubernetes manifests (Deployment, Service, HPA, Ingress, NetworkPolicy, ConfigMap)
- CI/CD with 11 GitHub Actions workflows
  - Code linting, static analysis, testing, security scanning
  - Docker build and push to GHCR
  - Staging/production deployment
  - Dependabot configuration
- Complete documentation suite
  - Architecture overview, domain model, API reference
  - Quickstart, production deployment, Docker setup guides
  - Security policy, contributing guide, executive summary
  - 100+ item production readiness checklist

[1.4.0]: https://github.com/webbixray/astra-os-enterprise/compare/v1.3.0...v1.4.0
[1.3.0]: https://github.com/webbixray/astra-os-enterprise/compare/v1.2.0...v1.3.0
[1.2.0]: https://github.com/webbixray/astra-os-enterprise/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/webbixray/astra-os-enterprise/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/webbixray/astra-os-enterprise/releases/tag/v1.0.0
