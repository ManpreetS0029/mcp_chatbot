#!/bin/sh
set -e

# Ensure Laravel storage and cache directories exist with correct permissions
mkdir -p /var/www/storage/framework/cache/data \
         /var/www/storage/framework/sessions \
         /var/www/storage/framework/views \
         /var/www/storage/logs \
         /var/www/bootstrap/cache

touch /var/www/storage/logs/laravel.log

chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

if [ $# -eq 0 ]; then
    exec php-fpm
else
    exec "$@"
fi

