<?php

namespace App\Providers;

use App\Models\User;
use App\Support\UserPermissions;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request): Limit {
            $token = (string) $request->bearerToken();

            return Limit::perMinute(120)->by($token !== '' ? sha1($token) : $request->ip());
        });

        Gate::before(function (?User $user, string $ability, array $arguments = []): ?bool {
            if (! $user) {
                return null;
            }

            $action = UserPermissions::actionForAbility($ability);

            if ($action === null) {
                return $user->is_super_admin ? true : null;
            }

            return UserPermissions::allowsModel($user, $arguments[0] ?? null, $action);
        });
    }
}
