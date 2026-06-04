<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Throwable;

class UserCountryAccess
{
    /**
     * @var array<int, int>|null
     */
    private static ?array $allowedLocationIds = null;

    public static function user(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }

    public static function locationId(): ?int
    {
        return self::user()?->location_id;
    }

    public static function canViewAllCountries(): bool
    {
        return (bool) self::user()?->canViewAllCountries();
    }

    public static function canViewRegionalDashboard(): bool
    {
        return self::canViewAllCountries();
    }

    /**
     * @return array<int, int>
     */
    public static function allowedLocationIds(): array
    {
        if (self::$allowedLocationIds !== null) {
            return self::$allowedLocationIds;
        }

        $user = self::user();
        $countryLocationId = $user?->location_id;

        if (! $user || blank($countryLocationId)) {
            return self::$allowedLocationIds = [];
        }

        $assigned = [];

        if (Schema::hasTable('user_location_assignments')) {
            try {
                $assigned = $user->locationAssignments()
                    ->pluck('location_id')
                    ->map(fn ($locationId): int => (int) $locationId)
                    ->all();
            } catch (Throwable) {
                $assigned = [];
            }
        }

        return self::$allowedLocationIds = array_values(array_unique([
            (int) $countryLocationId,
            ...$assigned,
        ]));
    }

    public static function forgetCachedLocations(): void
    {
        self::$allowedLocationIds = null;
    }

    public static function scope(Builder $query, string $column = 'location_id'): Builder
    {
        if (self::canViewAllCountries()) {
            return $query;
        }

        $locationIds = self::allowedLocationIds();

        if ($locationIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn($query->getModel()->qualifyColumn($column), $locationIds);
    }

    public static function scopeLocations(Builder $query, string $column = 'location_id'): Builder
    {
        return self::scope($query, $column);
    }

    public static function allowsLocationId(mixed $locationId): bool
    {
        if (self::canViewAllCountries()) {
            return true;
        }

        if (blank($locationId)) {
            return false;
        }

        return in_array((int) $locationId, self::allowedLocationIds(), true);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function enforceLocationData(array $data, string $column = 'location_id'): array
    {
        if (self::canViewAllCountries()) {
            return $data;
        }

        if (array_key_exists($column, $data) && self::allowsLocationId($data[$column])) {
            return $data;
        }

        $locationId = self::locationId();

        if (filled($locationId)) {
            $data[$column] = (int) $locationId;
        }

        return $data;
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    public static function scopedRecordExists(string $modelClass, mixed $key, string $column = 'location_id'): bool
    {
        if (blank($key)) {
            return false;
        }

        if (self::canViewAllCountries()) {
            return true;
        }

        $query = $modelClass::query()->whereKey($key);
        self::scope($query, $column);

        return $query->exists();
    }

    public static function scopeDashboard(Builder $query, string $column = 'location_id'): Builder
    {
        return self::canViewRegionalDashboard()
            ? $query
            : self::scope($query, $column);
    }
}
