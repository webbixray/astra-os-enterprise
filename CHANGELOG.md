# Changelog

All notable changes to the Astra OS Enterprise project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Telegram bot integration for campaign management
- Webhook system for external platform integrations
- WebSocket real-time updates for campaign metrics
- Export/Import functionality for campaigns and analytics

### Changed
- Upgraded API rate limiting to per-user tiers
- Enhanced error response standardization

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
- Version bumped from 1.0.0 to 1.1.0
- TestCase.php with CreatesApplication trait for feature tests
- phpunit.xml with comprehensive coverage configuration
- Enhanced error handling in DTO constructors

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

[Unreleased]: https://github.com/webbixray/astra-os-enterprise/compare/v1.1.0...HEAD
[1.1.0]: https://github.com/webbixray/astra-os-enterprise/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/webbixray/astra-os-enterprise/releases/tag/v1.0.0
