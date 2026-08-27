# Performance and Load Testing

This note records the local load-test approach for the Data Capture Tool and the production posture needed before testing hundreds or thousands of connected users.

## Changes Applied in the Application

The application now defaults to production-grade shared services when `APP_ENV=production` and an explicit environment override is not provided:

- `CACHE_STORE=redis`
- `SESSION_DRIVER=redis`
- `QUEUE_CONNECTION=redis`
- `LOG_CHANNEL=stderr`

Redis connections also support production options such as TLS, persistent connections, retry settings, and read timeouts through environment variables.

Use `.env.azure.example` as the App Service configuration checklist. Do not commit real secrets.

## Local Result

The local Laravel app was started with:

```bash
php artisan serve --host=127.0.0.1 --port=8001
```

Laravel production caches were warmed before testing:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

The local test runner is:

```bash
npm run load:test -- http://127.0.0.1:8001/admin/af/login 1000 1000
```

On the development server, 1000 concurrent requests saturated `php artisan serve`. The same saturation happened on static files, which means the local server is the main bottleneck. These numbers should not be used as production capacity figures.

## Production Requirements for 1000 Connected Users

Use Azure App Service with a production PHP runtime and enough worker processes. Do not use `php artisan serve` for load testing or production.

Minimum Azure App Service settings:

```env
APP_ENV=production
APP_DEBUG=false
LOG_CHANNEL=stderr
CACHE_STORE=redis
SESSION_DRIVER=redis
SESSION_CONNECTION=cache
SESSION_STORE=redis
QUEUE_CONNECTION=redis
SESSION_SECURE_COOKIE=true
REDIS_CLIENT=phpredis
REDIS_SCHEME=tls
REDIS_PORT=6380
REDIS_PERSISTENT=true
```

Use Azure Cache for Redis for shared cache, sessions, rate limits, dashboard cache, UHC Clock cache, and notification counts. File-based cache and file-based sessions should not be used when the app is scaled to multiple workers or multiple instances.

Administrative notifications are delivered both inside the DCT and by email. In Azure App Service, configure a real mail transport instead of `MAIL_MAILER=log`, then keep these flags enabled:

```env
MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=no-reply@dct.afro.who.int
AHO_ADMIN_EMAIL_NOTIFICATIONS=true
AHO_ADMIN_ACTIVITY_NOTIFICATIONS=true
```

Country-scoped events notify regional administrators, the relevant country administrators, and country users with administrative permissions for that country. Global events notify regional administrators.

Before every production load test, run:

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize
php artisan app:production-readiness --fail
```

`php artisan app:production-readiness --fail` should pass before a 1000-user test is considered valid.

## Test Plan

Run load tests against the Azure staging slot, not the local development server.

Minimum scenarios:

1. 1000 connected users opening the dashboard.
2. 1000 connected users browsing indicator values.
3. 300 connected users filtering or searching large indicator tables.
4. 100 connected users opening UHC Clock progress after a cold cache.
5. 1000 connected users opening UHC Clock progress after a warm cache.
6. 50 users importing or creating indicator values while others browse.

Success targets:

```text
Error rate: < 1%
Dashboard p95: < 2s after warm cache
Indicator list p95: < 3s
UHC Clock warm p95: < 2s
UHC Clock cold p95: < 8s
```

If the app misses those targets on Azure, inspect database slow queries, Redis latency, PHP worker saturation, and outbound calls such as the chatbot provider.

## Azure Scaling Notes

Use at least a production App Service Plan that can run multiple PHP-FPM workers and scale out to more than one instance. A single low-tier instance should not be expected to carry 1000 active users on Filament/Livewire screens.

Recommended first production test posture:

- App Service: production tier, autoscale enabled.
- Redis: standard or premium tier, same region as the app.
- Database: monitor CPU, DTU/vCore, slow queries, lock waits, and connection count.
- Static assets: served by Nginx/App Service with long browser cache headers, or moved behind a CDN later.
- Imports and chatbot calls: keep them outside critical page-render paths where possible.

For authenticated load tests, capture a test user's session cookie from the browser and pass it as the fourth argument:

```bash
npm run load:test -- https://your-staging-slot.example/admin/af 1000 1000 "data-capture-tool-session=..."
```
