#!/bin/bash

cd /home/site/wwwroot

echo "=== Création des dossiers storage ==="
mkdir -p storage/framework/views
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/logs
mkdir -p bootstrap/cache

echo "=== Permissions ==="
chmod -R 775 storage
chmod -R 775 bootstrap/cache

echo "=== Configuration Nginx → /public ==="
cp /home/site/wwwroot/default /etc/nginx/sites-available/default
service nginx reload

echo "=== Composer install ==="
if [ -f /usr/local/bin/composer ]; then
    COMPOSER=/usr/local/bin/composer
elif [ -f /usr/bin/composer ]; then
    COMPOSER=/usr/bin/composer
else
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
    COMPOSER=/usr/local/bin/composer
fi

$COMPOSER install --no-dev --optimize-autoloader --no-interaction

echo "=== Artisan ==="
php artisan config:clear
php artisan view:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "=== Démarrage PHP-FPM ==="
php-fpm