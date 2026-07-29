# Docker Setup Guide

## Overview

Astra OS uses Docker Compose for local development and Docker Swarm/Kubernetes for production. This guide covers the Docker setup.

## Services

| Service | Image | Purpose |
|---------|-------|---------|
| `app` | Custom PHP 8.4 FPM | PHP application server |
| `web` | Nginx 1.27 | Reverse proxy & static files |
| `db` | PostgreSQL 16 | Primary database |
| `cache` | Redis 7 | Cache & session store |
| `queue` | Custom PHP CLI | Queue worker (Redis) |
| `scheduler` | Custom PHP CLI | Cron scheduler |
| `mailpit` | axllent/mailpit | Email testing UI |

## Quick Start

```bash
# Build and start all services
docker compose -f docker/docker-compose.yml up -d --build

# View logs
docker compose logs -f

# Stop all services
docker compose down
```

## Production Mode

```bash
# Start with production overrides (resource limits, restart policies)
docker compose -f docker/docker-compose.yml -f docker/docker-compose.prod.yml up -d

# Scale queue workers
docker compose -f docker/docker-compose.yml -f docker/docker-compose.prod.yml up -d --scale queue=3
```

## Building Images

### Development Build

```bash
# Build PHP-FPM image
docker build -f docker/php/Dockerfile -t astraos/app:latest .

# Build with cache
docker build --cache-from astraos/app:latest -f docker/php/Dockerfile -t astraos/app:latest .
```

### Production Build

```bash
# Multi-stage build with optimizations
docker build \
  --target app \
  --build-arg APP_ENV=production \
  -f docker/php/Dockerfile \
  -t ghcr.io/astraos/app:1.0.0 .
```

## Docker Compose Configuration

### Environment Variables

Create a `.env` file:

```env
APP_ENV=local
APP_DEBUG=true
APP_PORT=80
DB_DATABASE=astraos
DB_USERNAME=astraos
DB_PASSWORD=astraos
DB_EXTERNAL_PORT=5432
REDIS_EXTERNAL_PORT=6379
MAILPIT_WEB_PORT=8025
MAILPIT_SMTP_PORT=1025
OPENAI_API_KEY=
```

### Volumes

```yaml
volumes:
  postgres_data:     # Database persistence
  redis_data:         # Cache persistence
  app_storage:        # Application storage
```

### Networking

```yaml
networks:
  astraos:
    driver: bridge
    name: astraos-network
```

## Container Health Checks

All services have health checks:

```bash
# Check application health
docker compose exec app healthcheck.sh

# Check database
docker compose exec db pg_isready -U astraos

# Check cache
docker compose exec cache redis-cli ping
```

## Image Structure

### PHP-FPM Image (docker/php/Dockerfile)

```
FROM php:8.4-fpm-alpine

Stage 1: composer
  - Install PHP dependencies
  
Stage 2: app
  - Install system packages
  - PHP extensions (pdo_pgsql, redis, gd, etc.)
  - Copy application code
  - Configure PHP-FPM
  - Configure Nginx
  - Setup supervisor
```

### Multi-stage Build Benefits

- **Smaller images** - Only production dependencies
- **Faster deployments** - Cache layers effectively
- **Security** - No build tools in production image
- **Consistency** - Same image across environments

## Docker Compose Profiles

The production override file uses profiles to exclude development services:

```yaml
mailpit:
  profiles:
    - dev  # Only starts in development mode
```

## Troubleshooting

### Container fails to start
```bash
docker compose logs app
docker compose logs db
```

### Database connection issues
```bash
docker compose exec db psql -U astraos -d astraos -c "SELECT 1"
```

### Permission issues
```bash
docker compose exec app chmod -R 775 storage bootstrap/cache
```

### Rebuild after code changes
```bash
docker compose build app
docker compose up -d app
```

## Best Practices

1. **Never use `:latest` in production** - Always tag specific versions
2. **Use `.dockerignore`** - Exclude `node_modules`, `.git`, etc.
3. **Set resource limits** - Prevent resource exhaustion
4. **Use health checks** - Ensure containers are ready
5. **Log to stdout/stderr** - Docker native logging
6. **Run as non-root** - Security best practice
7. **Use multi-stage builds** - Smaller images
8. **Cache dependencies** - Faster builds
