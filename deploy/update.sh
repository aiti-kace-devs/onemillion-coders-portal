cd ../ && git pull \
&& sudo chown -R $USER:$USER . \
&& composer install --no-dev \
&& npm run build \
&& sudo su www-data -s /bin/bash -c 'php artisan horizon:pause && php artisan migrate --force && php artisan optimize:clear && php artisan route:cache && php artisan view:cache && php artisan horizon:continue' \
&& cd deploy \
&& cat ./fix-permissions.sh | sudo -S bash \
&& cat ./restart-services.sh | sudo -S bash
