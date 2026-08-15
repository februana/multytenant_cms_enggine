FROM composer:2 AS composer

FROM php:8.3-apache

ENV APACHE_DOCUMENT_ROOT=/var/www/html \
    PORT=10000 \
    UNDANGAN_DATA_DIR=/var/data \
    UNDANGAN_DB_PATH=/var/data/database.sqlite

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
        libonig-dev \
        libsqlite3-dev \
        sqlite3 \
        libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# Layer 2: Configure & Install GD
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" gd

# Layer 3: Install Mbstring
RUN docker-php-ext-install -j"$(nproc)" mbstring

# Layer 4: Install SQLite
RUN docker-php-ext-install -j"$(nproc)" pdo_sqlite sqlite3

# Layer 5: Install Zip
RUN docker-php-ext-install -j"$(nproc)" zip

# Layer 6: Enable Apache modules
RUN a2enmod rewrite headers expires

# Copy Composer binary
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Layer 7: Install Composer dependencies
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader

# Layer 8: Copy application files & entrypoint
COPY . .
COPY docker/render-entrypoint.sh /usr/local/bin/render-entrypoint.sh
COPY docker/render-prepend.php /usr/local/bin/render-prepend.php
COPY docker/render.ini /usr/local/etc/php/conf.d/zz-render.ini

RUN chmod +x /usr/local/bin/render-entrypoint.sh \
    && mkdir -p /var/data /var/www/html/uploads /var/www/html/backups /var/www/html/webdav \
    && chown -R www-data:www-data /var/www/html /var/data

COPY docker/000-default.conf /etc/apache2/sites-available/000-default.conf

EXPOSE 10000

ENTRYPOINT ["/usr/local/bin/render-entrypoint.sh"]
CMD ["apache2-foreground"]
