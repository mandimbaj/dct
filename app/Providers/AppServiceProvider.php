<?php

namespace App\Providers;

use App\Models\User;
use App\Support\UserPermissions;
use Illuminate\Support\Facades\Gate;
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
