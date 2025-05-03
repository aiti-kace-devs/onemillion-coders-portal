git pull && composer update --no-dev && php artisan migrate --force && php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache \
&& sudo chown -R www-data:www-data storage && sudo chown -R www-data:www-data bootstrap/cache \
&& sudo systemctl restart php8.2-fpm && sudo systemctl restart nginx
