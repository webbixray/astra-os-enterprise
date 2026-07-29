# syntax = docker/dockerfile:1.4

# Stage 1: Dependencies
FROM composer/composer:2-bin AS composer-bin

# Stage 2: PHP Dependencies
FROM php:8.4-fpm-alpine AS vendor

RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    zip \
    unzip \
    curl \
    git \
    icu-dev \
    oniguruma-dev \
    libxml2-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_pgsql \
    pgsql \
    mbstring \
    xml \
    bcmath \
    gd \
    zip \
    intl \
    opcache \
    exif \
    pcntl

COPY --from=composer-bin /composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --no-suggest --optimize-autoloader --prefer-dist

# Stage 3: Application Build
FROM vendor AS app-build

COPY . .

RUN php artisan config:clear \
    && php artisan route:cache \
    && php artisan view:cache

# Stage 4: Production Image
FROM php:8.4-fpm-alpine AS production

RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    zip \
    unzip \
    curl \
    icu-dev \
    oniguruma-dev \
    libxml2-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_pgsql \
    pgsql \
    mbstring \
    xml \
    bcmath \
    gd \
    zip \
    intl \
    opcache \
    exif \
    pcntl

# Production PHP config
COPY docker/php/php.ini /usr/local/etc/php/php.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

WORKDIR /app

RUN addgroup -g 1000 -S astra \
    && adduser -u 1000 -S astra -G astra

COPY --from=app-build --chown=astra:astra /app /app
COPY --from=vendor --chown=astra:astra /app/vendor /app/vendor
COPY --chown=astra:astra docker/scripts/entrypoint.sh /usr/local/bin/entrypoint.sh
COPY --chown=astra:astra docker/scripts/healthcheck.sh /usr/local/bin/healthcheck.sh

RUN chmod +x /usr/local/bin/entrypoint.sh /usr/local/bin/healthcheck.sh \
    && mkdir -p /app/storage/logs /app/storage/framework/cache/data /app/storage/framework/sessions /app/storage/framework/views /app/storage/app/public \
    && chown -R astra:astra /app/storage /app/bootstrap/cache

USER astra

EXPOSE 9000

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD healthcheck.sh

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]

# Stage 5: Development Image
FROM php:8.4-fpm-alpine AS development

RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    zip \
    unzip \
    curl \
    git \
    icu-dev \
    oniguruma-dev \
    libxml2-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    linux-headers \
    $PHPIZE_DEPS \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_pgsql \
    pgsql \
    mbstring \
    xml \
    bcmath \
    gd \
    zip \
    intl \
    opcache \
    exif \
    pcntl

COPY --from=composer-bin /composer /usr/bin/composer

# Development PHP config
COPY docker/php/php.ini /usr/local/etc/php/php.ini
COPY docker/php/php-dev.ini /usr/local/etc/php/conf.d/99-dev.ini

WORKDIR /app

RUN addgroup -g 1000 -S astra \
    && adduser -u 1000 -S astra -G astra

COPY --chown=astra:astra . /app

RUN mkdir -p /app/storage/logs /app/storage/framework/cache/data /app/storage/framework/sessions /app/storage/framework/views /app/storage/app/public \
    && chown -R astra:astra /app/storage /app/bootstrap/cache

USER astra

EXPOSE 9000
