<?php

namespace App\Providers;

use App\Models\NationalObservatory;
use App\Models\User;
use App\Support\AdminActivityNotifier;
use App\Support\SelectOptions;
use App\Support\UserCountryAccess;
use App\Support\UserPermissions;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
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
        Schema::defaultStringLength(191);
        AdminActivityNotifier::observeApplicationModels();

        Select::configureUsing(static function (Select $select): void {
            $select->optionsLimit(SelectOptions::LIMIT);
        });

        SelectFilter::configureUsing(static function (SelectFilter $filter): void {
            $filter->optionsLimit(SelectOptions::LIMIT);
        });

        DeleteAction::configureUsing(static function (DeleteAction $action): void {
            $action
                ->tooltip(__('aho.actions.delete_confirmation_tooltip'))
                ->modalDescription(__('aho.actions.delete_confirmation_description'));
        });

        DeleteBulkAction::configureUsing(static function (DeleteBulkAction $action): void {
            $action
                ->tooltip(__('aho.actions.delete_bulk_confirmation_tooltip'))
                ->modalDescription(__('aho.actions.delete_bulk_confirmation_description'));
        });

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

            $model = $arguments[0] ?? null;

            if (
                $model instanceof NationalObservatory
                && in_array($action, [UserPermissions::ACTION_UPDATE, UserPermissions::ACTION_DELETE], true)
            ) {
                return UserCountryAccess::allowsLocationId($model->location_id);
            }

            return UserPermissions::allowsModel($user, $model, $action);
        });

    }
}
