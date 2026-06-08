#!/bin/bash
cd /home/site/wwwroot

# Installer les dépendances
composer install --no-dev --optimize-autoloader

# Configurer Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force

# Démarrer Nginx avec le bon document root
echo "root /home/site/wwwroot/public;" > /etc/nginx/conf.d/laravel.conf