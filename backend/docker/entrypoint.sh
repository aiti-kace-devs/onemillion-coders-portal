#!/usr/bin/env bash

if [[ $# -gt 0 ]]; then
    exec "$@"
else
    chown -R www-data:www-data storage bootstrap/cache storage/logs
    find storage bootstrap/cache -type d -exec chmod 775 {} + && find storage bootstrap/cache -type f -exec chmod 664 {} +
    find storage/logs -type d -exec chmod 775 {} + && find storage/logs -type f -exec chmod 664 {} +
    find storage/framework -type d -exec chmod 775 {} + && find storage/framework -type f -exec chmod 664 {} +
    find storage/framework/cache -type d -exec chmod 775 {} + && find storage/framework/cache -type f -exec chmod 664 {} +
    find storage/framework/sessions -type d -exec chmod 775 {} + && find storage/framework/sessions -type f -exec chmod 664 {} +
    find storage/framework/views -type d -exec chmod 775 {} + && find storage/framework/views -type f -exec chmod 664 {} +
    find storage/statamic -type d -exec chmod 775 {} + && find storage/statamic -type f -exec chmod 664 {} +
    find storage/statamic/stache-locks -type d -exec chmod 775 {} + && find storage/statamic/stache-locks -type f -exec chmod 664 {} +



    if [[ "${RUN_QUEUE_WORKER:-false}" == "true" ]]; then
        echo "Running as QUEUE WORKER - disabling Nginx and FPM"
        rm -f /etc/supervisor/conf.d/fpm.conf /etc/supervisor/conf.d/nginx.conf
    else
          # If vendor is missing (e.g. during development with a volume mount), install dependencies
        if [ ! -f vendor/autoload.php ]; then
            echo "Vendor directory not found. Running composer install..."
            composer install --no-dev --optimize-autoloader --no-scripts
        fi
        echo "Running as WEB SERVER - disabling Queue Worker"
        rm -f /etc/supervisor/conf.d/queue-worker.conf
        php artisan app:setup-application
    fi

    exec supervisord -n -c /etc/supervisor/supervisord.conf
fi

