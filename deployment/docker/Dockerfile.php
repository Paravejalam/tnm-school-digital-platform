# =============================================================================
# T.N. Memorial Public School Digital Platform
# Dockerfile.php — PHP 8.3 FPM Backend Runtime
# =============================================================================

FROM php:8.3-fpm

LABEL maintainer="T.N. Memorial School Platform Team"
LABEL description="PHP 8.3 FPM backend runtime for TNM School Digital Platform"
LABEL version="1.0.0"

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    libssl-dev \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo \
        pdo_mysql \
        mbstring \
        zip \
        intl \
        gd \
        fileinfo \
        opcache \
        exif \
        bcmath

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY deployment/docker/php/php.ini /usr/local/etc/php/conf.d/tnm-php.ini

WORKDIR /var/www/backend

COPY backend/composer.json ./composer.json

RUN composer install \
    --no-interaction \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader

RUN chown -R www-data:www-data /var/www/backend \
    && chmod -R 755 /var/www/backend

USER www-data

EXPOSE 9000

CMD ["php-fpm"]
