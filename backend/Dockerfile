FROM php:8.3-fpm

# Environment variables
ENV PHP_PM_MAX_CHILDREN=12 \
    PHP_PM_START_SERVERS=3 \
    PHP_MIN_SPARE_SERVERS=2 \
    PHP_MAX_SPARE_SERVERS=4 \
    PHP_DATE_TIMEZONE=UTC \
    PHP_MEMORY_LIMIT=1G \
    NGINX_VERSION=1.25.3 \
    PHP_VERSION=8.3 \
    PHP_OPEN_BASEDIR=/var/www/html:/dev/stdout:/tmp:/var/www:/var/www/.config/gcloud \
    COMPOSER_ALLOW_SUPERUSER=1

# COPY docker/php/ondrej_ubuntu_php.gpg /etc/apt/trusted.gpg.d/ondrej_ubuntu_php.gpg

# Install system dependencies
RUN apt-get update && apt-get install -y \
    nginx \
    supervisor \
    git \
    curl \
    unzip \
    libpng-dev \
    libwebp-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libmemcached-dev \
    libzip-dev \
    libssl-dev \
    default-mysql-client \
    libpq-dev zlib1g-dev \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql zip opcache bcmath pcntl posix exif \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp --with-png \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configure PHP
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/php/php.ini /usr/local/etc/php/conf.d/php.ini

# COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-custom.ini

# Configure Nginx
# COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
# COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
# RUN echo "daemon off;" >> /etc/nginx/nginx.conf
# Copy configuration files (these are stable, so they come before application code)
COPY docker/nginx/ /etc/nginx/
COPY docker/nginx/sites-available/default /etc/nginx/sites-enabled/default.conf

COPY docker/fpm/pool.d/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/supervisor/ /etc/supervisor/
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
COPY docker/start-nginx.sh /usr/local/bin/start-nginx
RUN chmod +x /usr/local/bin/start-nginx


# Application setup
WORKDIR /var/www/html
COPY composer.json composer.lock ./
COPY composerauth.json ./auth.json

# Install dependencies and optimize
# ENV CACHE_STORE=memcached
# ENV SESSION_DRIVER=memcached

# Run composer install. This layer will only be rebuilt if composer.json/lock changes.
# Add --no-scripts if you don't need composer scripts to run here (e.g., Laravel's post-install-cmd)
RUN composer install --no-dev --optimize-autoloader --no-scripts \
    && rm -rf /composer/cache

COPY . .

RUN chown -R www-data:www-data /var/www/html \
    && mkdir -p  bootstrap/cache storage/framework/sessions storage/app/public/basset storage/logs storage/statamic storage/statamic/stache-locks \
    && mkdir -p  bootstrap/cache storage/framework/views \
    && mkdir -p  bootstrap/cache storage/framework/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 8080

ENTRYPOINT ["/entrypoint.sh"]
