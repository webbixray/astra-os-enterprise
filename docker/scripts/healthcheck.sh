#!/bin/sh
set -e

# Check PHP-FPM
if ! php-fpm -t 2>/dev/null; then
    echo "PHP-FPM configuration error"
    exit 1
fi

# Check if PHP-FPM is running
if ! pgrep -x php-fpm > /dev/null; then
    echo "PHP-FPM is not running"
    exit 1
fi

# Check database connection
if [ -n "$DB_HOST" ]; then
    if ! php -r "new PDO('pgsql:host=${DB_HOST};port=${DB_PORT:-5432};dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD});" 2>/dev/null; then
        echo "Database connection failed"
        exit 1
    fi
fi

# Check Redis connection
if [ -n "$REDIS_HOST" ]; then
    if ! php -r "new Redis()->connect('${REDIS_HOST}', ${REDIS_PORT:-6379});" 2>/dev/null; then
        echo "Redis connection failed"
        exit 1
    fi
fi

echo "Health check passed"
exit 0
