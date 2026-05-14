<?php

namespace App\Filament\Resources\Concerns;

use App\Support\UserCountryAccess;
use Illuminate\Database\Eloquent\Builder;

trait ScopesFacilityCountryAccess
{
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (UserCountryAccess::canViewAllCountries()) {
            return $query;
        }

        $locationIds = UserCountryAccess::allowedLocationIds();

        if ($locationIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas(
            'facility',
            fn (Builder $facilityQuery): Builder => $facilityQuery->whereIn('location_id', $locationIds),
        );
    }
}
