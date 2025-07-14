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
    php artisan app:setup-application
    exec supervisord -n -c /etc/supervisor/supervisord.conf
fi

