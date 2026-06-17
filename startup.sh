#!/bin/bash

APP_DIR=/home/site/wwwroot

echo "=== [1] Création dossiers storage ==="
mkdir -p $APP_DIR/storage/framework/views
mkdir -p $APP_DIR/storage/framework/cache/data
mkdir -p $APP_DIR/storage/framework/sessions
mkdir -p $APP_DIR/storage/logs
mkdir -p $APP_DIR/bootstrap/cache
chmod -R 777 $APP_DIR/storage
chmod -R 777 $APP_DIR/bootstrap/cache

echo "=== [2] Config Nginx → /public ==="
cat > /etc/nginx/sites-available/default << 'EOF'
server {
    listen 8080;
    listen [::]:8080;
    server_name _;

    root /home/site/wwwroot/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

echo "=== [3] Test config Nginx ==="
nginx -t

echo "=== [4] Reload Nginx ==="
service nginx reload || nginx -s reload

echo "=== [5] Composer ==="
if ! [ -x "$(command -v composer)" ]; then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi
cd $APP_DIR && composer install --no-dev --optimize-autoloader --no-interaction

echo "=== [6] Artisan ==="
cd $APP_DIR
php artisan config:clear
php artisan view:clear  
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "=== [7] Vérification finale ==="
echo "Root Nginx :"
grep -r "root" /etc/nginx/sites-available/default
echo "PHP-FPM démarrage..."

php-fpm