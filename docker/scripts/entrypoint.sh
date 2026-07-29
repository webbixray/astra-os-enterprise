#!/bin/sh
set -e

# Wait for database to be ready
if [ -n "$DB_HOST" ]; then
    echo "Waiting for database connection..."
    until php -r "try { new PDO('pgsql:host=${DB_HOST};port=${DB_PORT:-5432};dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}'); echo 'connected'; } catch(PDOException \$e) { echo \$e->getMessage(); exit(1); }" 2>/dev/null | grep -q "connected"; do
        sleep 1
    done
    echo "Database connected."
fi

# Wait for Redis
if [ -n "$REDIS_HOST" ]; then
    echo "Waiting for Redis..."
    until php -r "try { new Redis()->connect('${REDIS_HOST}', ${REDIS_PORT:-6379}); echo 'connected'; } catch(Exception \$e) { echo \$e->getMessage(); exit(1); }" 2>/dev/null | grep -q "connected"; do
        sleep 1
    done
    echo "Redis connected."
fi

# Run migrations on startup (only if not already run)
if [ "${APP_ENV}" != "local" ]; then
    echo "Running migrations..."
    php artisan migrate --force --isolated
    echo "Migrations complete."
fi

# Optimize for production
if [ "${APP_ENV}" != "local" ]; then
    echo "Optimizing application..."
    php artisan optimize
    php artisan event:cache
    php artisan view:cache
    echo "Optimization complete."
fi

# Start PHP-FPM
echo "Starting PHP-FPM..."
exec php-fpm
