<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

class DashboardCache
{
    public static function remember(string $key, Closure $callback, int $minutes = 60): mixed
    {
        return Cache::remember(
            static::key($key),
            now()->addMinutes($minutes),
            $callback,
        );
    }

    private static function key(string $key): string
    {
        $scope = UserCountryAccess::canViewRegionalDashboard()
            ? 'global'
            : 'country:'.(UserCountryAccess::locationId() ?? 'none');

        return implode(':', [
            'dashboard',
            'v13',
            app()->getLocale(),
            $scope,
            $key,
        ]);
    }
}
