FROM php:8.3-fpm-bookworm

# Environment variables
ENV PHP_PM_MAX_CHILDREN=12 \
    NODE_VERSION=20.18.0 \
    PHP_PM_START_SERVERS=3 \
    PHP_MIN_SPARE_SERVERS=2 \
    PHP_MAX_SPARE_SERVERS=4 \
    PHP_DATE_TIMEZONE=UTC \
    PHP_MEMORY_LIMIT=1G \
    NGINX_VERSION=1.25.3 \
    PHP_VERSION=8.3 \
    PHP_OPEN_BASEDIR=/var/www/html:/dev/stdout:/tmp:/var/www:/var/www/.config/gcloud \
    COMPOSER_ALLOW_SUPERUSER=1


# Install apt packages with --no-triggers, then run triggers with XZ_OPTIONS so dpkg trigger scripts (e.g. libc-bin) see it and xz sandbox does not fail
RUN apt-get update && apt-get install -y \
    nginx \
    supervisor \
    git \
    curl \
    unzip \
    gnupg \
    ca-certificates \
    libpng-dev \
    libwebp-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    libssl-dev \
    default-mysql-client \
    libpq-dev \
    zlib1g-dev \
    $PHPIZE_DEPS \
      # 1. Install Node.js using NodeSource setup script
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    # 2. Configure and install PHP extensions
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql zip opcache bcmath pcntl posix exif \
    && pecl install redis \
    && docker-php-ext-enable redis \
    # 5. Cleanup
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
COPY docker/scripts/run-queue-worker.sh /usr/local/bin/run-queue-worker
RUN chmod +x /usr/local/bin/run-queue-worker


# Application setup
WORKDIR /var/www/html
COPY composer.json composer.lock package.json package-lock.json* ./

COPY auth.json ./auth.json

# Install dependencies and optimize
# ENV CACHE_STORE=memcached
# ENV SESSION_DRIVER=memcached

# Run composer install. This layer will only be rebuilt if composer.json/lock changes.
COPY patches/ /patches/

RUN composer install --no-dev --optimize-autoloader --no-scripts \
    && rm -rf /composer/cache \
    && patch -p1 -d vendor/studio-42/elfinder \
       < /patches/elfinder-php83.patch

# Install npm dependencies
RUN npm install

COPY . .

# Build assets
# RUN npm run build

RUN chown -R www-data:www-data /var/www/html \
    && mkdir -p  bootstrap/cache storage/framework/sessions storage/app/public/basset storage/logs storage/statamic storage/statamic/stache-locks  resources/views/mail/temp \
    && mkdir -p  bootstrap/cache storage/framework/views \
    && mkdir -p  bootstrap/cache storage/framework/cache storage/framework/cache/data\
    && chmod -R 775 storage bootstrap/cache 

EXPOSE 8080

ENTRYPOINT ["/entrypoint.sh"]
