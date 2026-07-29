# Quick Start Guide

> Local development setup for Astra OS

## Prerequisites

- **PHP 8.4+** with extensions: pdo, mbstring, xml, curl, gd, redis, intl, bcmath
- **Composer 2.x**
- **PostgreSQL 16+**
- **Redis 7+**
- **Node.js 20+ & NPM** (for frontend assets)
- **Docker & Docker Compose** (optional, recommended)

## Option 1: Local Setup (No Docker)

### Step 1: Clone & Install

```bash
git clone https://github.com/astraos/astraos.git
cd astraos

# Install PHP dependencies
composer install

# Install Node dependencies
npm install
npm run build
```

### Step 2: Environment Configuration

```bash
cp .env.example .env

# Edit .env with your database credentials
# DB_CONNECTION=pgsql
# DB_HOST=127.0.0.1
# DB_PORT=5432
# DB_DATABASE=astraos
# DB_USERNAME=astraos
# DB_PASSWORD=secret
```

### Step 3: Generate Key & Setup Database

```bash
php artisan key:generate

# Create the database
createdb astraos

# Run migrations and seeders
php artisan migrate
php artisan db:seed
```

### Step 4: Start Services

```bash
# Terminal 1: Web server
php artisan serve

# Terminal 2: Queue worker
php artisan queue:work

# Terminal 3: Vite dev server (if using frontend)
npm run dev
```

### Step 5: Verify

```bash
curl http://localhost:8000/api/v1/health
```

## Option 2: Docker Setup (Recommended)

### Step 1: Start Services

```bash
# Build and start all services
docker compose -f docker/docker-compose.yml up -d --build
```

### Step 2: Setup Application

```bash
# Generate app key
docker compose exec app php artisan key:generate

# Run migrations
docker compose exec app php artisan migrate

# Seed database
docker compose exec app php artisan db:seed
```

### Step 3: Verify

```bash
curl http://localhost:8080/api/v1/health
```

## Option 3: One-Command Setup

```bash
# Clone and setup in one go
git clone https://github.com/astraos/astraos.git
cd astraos
make setup
```

> This requires `make` and assumes Docker is installed.

## Configuration Reference

### Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `APP_ENV` | Application environment | `local` |
| `APP_DEBUG` | Debug mode | `true` |
| `APP_KEY` | Application encryption key | (generated) |
| `APP_URL` | Application URL | `http://localhost:8000` |
| `DB_CONNECTION` | Database driver | `pgsql` |
| `DB_HOST` | Database host | `127.0.0.1` |
| `DB_PORT` | Database port | `5432` |
| `DB_DATABASE` | Database name | `astraos` |
| `DB_USERNAME` | Database user | `astraos` |
| `DB_PASSWORD` | Database password | `secret` |
| `REDIS_HOST` | Redis host | `127.0.0.1` |
| `REDIS_PORT` | Redis port | `6379` |
| `REDIS_PASSWORD` | Redis password | `null` |
| `OPENAI_API_KEY` | OpenAI API key | (required for agents) |
| `ANTHROPIC_API_KEY` | Anthropic API key | (optional) |

### Config Files Location

All Astra OS configuration files are in `config/`:

```bash
config/astra-os/general.php      # App settings
config/astra-os/features.php     # Feature flags
config/agents/providers.php      # AI providers
config/agents/roles.php          # Agent roles
config/campaigns/platforms.php   # Platform configs
config/campaigns/defaults.php    # Campaign defaults
config/workflows/nodes.php       # Workflow nodes
config/workflows/templates.php   # Workflow templates
```

## Available Artisan Commands

```bash
php artisan astra-os:setup                              # Full application setup
php artisan astra-os:agents:process-tasks               # Process agent tasks
php artisan astra-os:agents:prune-memory                # Clean agent memories
php artisan astra-os:campaigns:sync                     # Sync platform data
php artisan astra-os:campaigns:generate-reports         # Generate reports
php artisan astra-os:social:monitor-mentions            # Monitor mentions
php artisan astra-os:social:publish-scheduled           # Publish posts
php artisan astra-os:maintenance:cleanup-audit-logs     # Clean audit logs
```

## Login Credentials (After Seeding)

- **Email**: admin@astraos.io
- **Password**: password

## Troubleshooting

### Common Issues

**Q: Database connection refused**
```bash
# Ensure PostgreSQL is running
pg_isready
# Or via Docker
docker compose ps db
```

**Q: Redis connection refused**
```bash
# Ensure Redis is running
redis-cli ping
# Or via Docker
docker compose ps cache
```

**Q: Permission denied for storage**
```bash
chmod -R 775 storage bootstrap/cache
```

**Q: Class not found errors**
```bash
composer dump-autoload
```

### Getting Help

- GitHub Issues: https://github.com/astraos/astraos/issues
- Documentation: https://docs.astraos.io
- Email: dev@astraos.io
