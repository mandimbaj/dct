<?php

namespace App\Support;

use App\Models\Country;
use App\Models\User;
use App\Models\UserLocationAssignment;
use Illuminate\Support\Facades\Schema;
use Throwable;

class UserLocationAssignments
{
    public static function syncFor(User $user): void
    {
        if (! Schema::hasTable('user_location_assignments')) {
            return;
        }

        if ($user->canViewAllCountries() || blank($user->location_id)) {
            UserLocationAssignment::query()
                ->where('user_id', $user->id)
                ->delete();

            UserCountryAccess::forgetCachedLocations();

            return;
        }

        try {
            $locations = Country::query()
                ->where('parent_id', $user->location_id)
                ->get(['location_id', 'parent_id', 'locationlevel_id']);
        } catch (Throwable) {
            return;
        }

        $locationIds = $locations->pluck('location_id')->all();

        UserLocationAssignment::query()
            ->where('user_id', $user->id)
            ->when(
                $locationIds !== [],
                fn ($query) => $query->whereNotIn('location_id', $locationIds),
            )
            ->delete();

        foreach ($locations as $location) {
            UserLocationAssignment::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'location_id' => $location->location_id,
                ],
                [
                    'country_location_id' => $user->location_id,
                    'locationlevel_id' => $location->locationlevel_id,
                ],
            );
        }

        UserCountryAccess::forgetCachedLocations();
    }
}
