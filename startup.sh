#!/bin/bash

set -Eeuo pipefail

APP_DIR=/home/site/wwwroot
NGINX_SITE=/etc/nginx/sites-available/default
NGINX_ENABLED=/etc/nginx/sites-enabled/default
PORT="${PORT:-8080}"

echo "=== AHO DCT Azure startup ==="
echo "App directory: ${APP_DIR}"
echo "Nginx port: ${PORT}"
cd "${APP_DIR}"

echo "=== [1] Storage and cache folders ==="
mkdir -p storage/framework/views
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/logs
mkdir -p bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache || true

echo "=== [2] Composer dependencies ==="
if ! command -v composer >/dev/null 2>&1; then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

if [ ! -f vendor/autoload.php ]; then
    composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
else
    composer dump-autoload --no-dev --optimize --no-interaction
fi

echo "=== [3] Laravel cache warmup ==="
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true
php artisan cache:clear || true
php artisan package:discover --ansi || true
php artisan config:cache
php artisan view:cache

echo "=== [4] Configure PHP-FPM to listen on 127.0.0.1:9000 ==="
FPM_POOL_CONFIG=""
for candidate in \
    /usr/local/etc/php-fpm.d/www.conf \
    /etc/php/*/fpm/pool.d/www.conf \
    /etc/php-fpm.d/www.conf
do
    if [ -f "${candidate}" ]; then
        FPM_POOL_CONFIG="${candidate}"
        break
    fi
done

if [ -n "${FPM_POOL_CONFIG}" ]; then
    echo "PHP-FPM pool config: ${FPM_POOL_CONFIG}"
    sed -i 's#^listen = .*#listen = 127.0.0.1:9000#' "${FPM_POOL_CONFIG}"
else
    echo "WARNING: PHP-FPM pool config not found. Using default PHP-FPM listen settings."
fi

echo "=== [5] Configure Nginx root to Laravel public/ ==="
cat > "${NGINX_SITE}" <<EOF
server {
    listen ${PORT};
    listen [::]:${PORT};
    server_name _;

    root ${APP_DIR}/public;
    index index.php index.html;

    client_max_body_size 100M;
    client_header_buffer_size 32k;
    large_client_header_buffers 8 64k;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_buffer_size 32k;
        fastcgi_buffers 8 32k;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

ln -sf "${NGINX_SITE}" "${NGINX_ENABLED}" || true
nginx -t

echo "=== [6] Start or reload Nginx ==="
if pgrep -f "nginx: master" >/dev/null 2>&1; then
    nginx -s reload
else
    nginx
fi

echo "=== [7] Start PHP-FPM in foreground ==="
FPM_BIN=""
for candidate in php-fpm php-fpm8.5 php-fpm8.4 php-fpm8.3 php-fpm8.2; do
    if command -v "${candidate}" >/dev/null 2>&1; then
        FPM_BIN="${candidate}"
        break
    fi
done

if [ -z "${FPM_BIN}" ]; then
    echo "ERROR: php-fpm command was not found."
    exit 1
fi

echo "PHP-FPM binary: ${FPM_BIN}"
exec "${FPM_BIN}" -F
