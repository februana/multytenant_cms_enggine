FROM composer:2 AS composer

FROM php:8.3-apache

# Optimized Dockerfile for Ubuntu & Armbian Servers (x86_64 / ARM64)
# Non-interactive automated deployment setup bypassing interactive install.sh prompts

ENV APACHE_DOCUMENT_ROOT=/var/www/wedding \
    PORT=80 \
    UNDANGAN_DATA_DIR=/var/data \
    UNDANGAN_DB_PATH=/var/data/database.sqlite \
    DEBIAN_FRONTEND=noninteractive

# Layer 1: Install system packages and development libraries
RUN apt-get update && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        unzip \
        git \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libwebp-dev \
        imagemagick \
        ffmpeg \
        libonig-dev \
        libsqlite3-dev \
        sqlite3 \
        libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# Layer 2: Configure & Install PHP Extensions
# The official php:8.3-apache base image already builds pdo_sqlite and
# sqlite3 against its system SQLite library. Compile only extensions that
# are not provided by the base image; recompiling SQLite causes config.m4
# failures in docker-php-ext-install.
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" gd mbstring zip

# Layer 3: Enable Apache modules
RUN a2enmod rewrite headers expires

# Copy Composer binary
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/wedding

# Layer 4: Install Composer dependencies
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader

# Layer 5: Copy application files & entrypoint
COPY . .
COPY docker/entrypoint.sh /usr/local/bin/docker-entrypoint.sh
COPY docker/000-default.conf /etc/apache2/sites-available/000-default.conf

RUN chmod +x /usr/local/bin/docker-entrypoint.sh \
    && sed -i 's|/var/www/html|/var/www/wedding|g' /etc/apache2/apache2.conf \
    && sed -i 's|/var/www/html|/var/www/wedding|g' /etc/apache2/conf-available/docker-php.conf \
    && mkdir -p /var/data /var/www/wedding/uploads /var/www/wedding/uploads/theme-assets /var/www/wedding/backups \
    && chown -R www-data:www-data /var/www/wedding /var/data

EXPOSE 80

# Keep the image self-describing for Docker, Swarm, and platforms that read
# image metadata instead of docker-compose health settings.
HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=5 \
    CMD curl --fail --silent --show-error http://127.0.0.1/ >/dev/null || exit 1

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["apache2-foreground"]
