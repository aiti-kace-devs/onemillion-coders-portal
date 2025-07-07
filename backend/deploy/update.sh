cd /var/www/html/one-mil-coders && git pull \
&& composer install --no-dev \
&& npm run build \
&& sudo su www-data -s /bin/bash -c 'php artisan horizon:pause && php artisan migrate --force && php artisan optimize:clear && php artisan route:cache && php artisan view:cache && php artisan horizon:continue' \
&& cd deploy \
&& cat /var/www/html/one-mil-coders/deploy/fix-permissions.sh | sudo -S bash \
&& cat /var/www/html/one-mil-coders/deploy/restart-services.sh | sudo -S bash
