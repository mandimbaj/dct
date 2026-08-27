<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProductionReadinessCheck extends Command
{
    protected $signature = 'app:production-readiness {--fail : Return a non-zero exit code when a critical check fails}';

    protected $description = 'Check whether the application is configured for a high-traffic production deployment.';

    public function handle(): int
    {
        $checks = collect([
            $this->check('Application environment', app()->environment('production'), 'critical', 'APP_ENV should be production.'),
            $this->check('Debug disabled', config('app.debug') === false, 'critical', 'APP_DEBUG must be false.'),
            $this->check('Cache store', config('cache.default') === 'redis', 'critical', 'CACHE_STORE should be redis for multi-instance traffic.'),
            $this->check('Session driver', config('session.driver') === 'redis', 'critical', 'SESSION_DRIVER should be redis for shared sessions.'),
            $this->check('Queue connection', config('queue.default') === 'redis', 'warning', 'QUEUE_CONNECTION=redis keeps heavy work out of web requests.'),
            $this->check('Production logging', config('logging.default') === 'stderr', 'warning', 'LOG_CHANNEL=stderr works best with Azure log streaming.'),
            $this->check('Config cached', file_exists(base_path('bootstrap/cache/config.php')), 'critical', 'Run php artisan config:cache or php artisan optimize.'),
            $this->check('Routes cached', $this->routesAreCached(), 'warning', 'Run php artisan route:cache or php artisan optimize.'),
            $this->check('Redis extension/client', extension_loaded('redis') || class_exists('Predis\Client'), 'critical', 'Install phpredis or configure Predis before using Redis.'),
            $this->connectionCheck('Application database', config('database.default')),
            $this->connectionCheck('Warehouse database', 'warehouse'),
            $this->redisCacheCheck(),
        ]);

        $this->components->info('Production readiness checks');
        $this->table(
            ['Status', 'Level', 'Check', 'Detail'],
            $checks->map(fn (array $check): array => [
                $check['passed'] ? 'OK' : 'FAIL',
                strtoupper($check['level']),
                $check['name'],
                $check['detail'],
            ])->all(),
        );

        $criticalFailures = $checks
            ->where('passed', false)
            ->where('level', 'critical')
            ->count();

        if ($criticalFailures > 0) {
            $this->components->error("{$criticalFailures} critical production check(s) failed.");

            return $this->option('fail') ? self::FAILURE : self::SUCCESS;
        }

        $this->components->info('The production baseline is ready for load testing.');

        return self::SUCCESS;
    }

    /**
     * @return array{name: string, passed: bool, level: string, detail: string}
     */
    private function check(string $name, bool $passed, string $level, string $detail): array
    {
        return compact('name', 'passed', 'level', 'detail');
    }

    /**
     * @return array{name: string, passed: bool, level: string, detail: string}
     */
    private function connectionCheck(string $name, string $connection): array
    {
        try {
            DB::connection($connection)->select('select 1');

            return $this->check($name, true, 'critical', "Connection '{$connection}' responds.");
        } catch (Throwable $exception) {
            return $this->check($name, false, 'critical', $exception->getMessage());
        }
    }

    /**
     * @return array{name: string, passed: bool, level: string, detail: string}
     */
    private function redisCacheCheck(): array
    {
        if (config('cache.default') !== 'redis') {
            return $this->check('Redis cache round-trip', false, 'critical', 'CACHE_STORE is not redis.');
        }

        try {
            Cache::store('redis')->put('production-readiness:ping', 'ok', 10);

            return $this->check(
                'Redis cache round-trip',
                Cache::store('redis')->get('production-readiness:ping') === 'ok',
                'critical',
                'Redis accepts cache reads and writes.',
            );
        } catch (Throwable $exception) {
            return $this->check('Redis cache round-trip', false, 'critical', $exception->getMessage());
        }
    }

    private function routesAreCached(): bool
    {
        return file_exists(base_path('bootstrap/cache/routes.php'))
            || count(glob(base_path('bootstrap/cache/routes-*.php')) ?: []) > 0;
    }
}
