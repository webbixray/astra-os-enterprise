# Changelog

All notable changes to the Astra OS Enterprise project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
