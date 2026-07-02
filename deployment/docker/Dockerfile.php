# =============================================================================
# T.N. Memorial Public School Digital Platform
# Dockerfile.php — PHP 8.3 FPM Backend Runtime
# Authority: .github/AGENT.md (Section 4.1 — Docker Governance)
# =============================================================================

FROM php:8.3-fpm

LABEL maintainer="T.N. Memorial School Platform Team"
LABEL description="PHP 8.3 FPM backend runtime for TNM School Digital Platform"
LABEL version="1.0.0"

# ---------------------------------------------------------------------------
# System dependencies
# ---------------------------------------------------------------------------
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

# ---------------------------------------------------------------------------
# PHP extensions required by AGENT.md Section 4
# PDO, pdo_mysql, mbstring, openssl, zip, intl, gd, fileinfo, opcache
# ---------------------------------------------------------------------------
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

# ---------------------------------------------------------------------------
# Composer — latest stable
# ---------------------------------------------------------------------------
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# ---------------------------------------------------------------------------
# PHP configuration overlay
# ---------------------------------------------------------------------------
COPY php/php.ini /usr/local/etc/php/conf.d/tnm-php.ini

# ---------------------------------------------------------------------------
# Working directory and dependency install
# ---------------------------------------------------------------------------
WORKDIR /var/www/backend

COPY ../../backend/composer.json ./composer.json
RUN composer install --no-interaction --no-scripts --prefer-dist --optimize-autoloader 2>/dev/null || true

# ---------------------------------------------------------------------------
# Permissions — run as www-data
# ---------------------------------------------------------------------------
RUN chown -R www-data:www-data /var/www/backend \
    && chmod -R 755 /var/www/backend

USER www-data

EXPOSE 9000

CMD ["php-fpm"]