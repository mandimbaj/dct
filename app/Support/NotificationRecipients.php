<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class NotificationRecipients
{
    /**
     * @return Collection<int, User>
     */
    public static function forCountry(?int $locationId, ?int $exceptUserId = null): Collection
    {
        $locationId = filled($locationId) ? (int) $locationId : null;

        $users = User::query()
            ->with(['locationAssignments', 'role'])
            ->whereNotNull('email')
            ->where('email', '<>', '')
            ->where(function ($query) use ($locationId): void {
                $query->where('is_super_admin', true);
                $query->orWhere('can_view_all_countries', true);

                if (filled($locationId)) {
                    $query->orWhere(function (Builder $countryQuery) use ($locationId): void {
                        $countryQuery
                            ->where(function (Builder $scopeQuery) use ($locationId): void {
                                $scopeQuery
                                    ->where('location_id', $locationId)
                                    ->orWhereHas('locationAssignments', function (Builder $assignmentQuery) use ($locationId): void {
                                        $assignmentQuery
                                            ->where('country_location_id', $locationId)
                                            ->orWhere('location_id', $locationId);
                                    });
                            })
                            ->where(function (Builder $adminQuery): void {
                                $adminQuery
                                    ->where('is_country_admin', true)
                                    ->orWhereNotNull('role_id')
                                    ->orWhereNotNull('menu_permissions');
                            });
                    });
                }
            })
            ->when($exceptUserId, fn ($query) => $query->whereKeyNot($exceptUserId))
            ->get();

        return $users
            ->filter(fn (User $user): bool => self::isRegionalAdministrator($user)
                || self::isCountryAdministratorFor($user, $locationId))
            ->unique('id')
            ->values();
    }

    private static function isRegionalAdministrator(User $user): bool
    {
        return (bool) ($user->is_super_admin || $user->can_view_all_countries);
    }

    private static function isCountryAdministratorFor(User $user, ?int $locationId): bool
    {
        if (! $locationId || ! self::isAssignedToLocation($user, $locationId)) {
            return false;
        }

        return (bool) $user->is_country_admin || self::hasAdministrativePermissions($user);
    }

    private static function isAssignedToLocation(User $user, int $locationId): bool
    {
        if ((int) $user->location_id === $locationId) {
            return true;
        }

        return $user->locationAssignments
            ->contains(fn ($assignment): bool => (int) $assignment->country_location_id === $locationId
                || (int) $assignment->location_id === $locationId);
    }

    private static function hasAdministrativePermissions(User $user): bool
    {
        $permissions = UserPermissions::permissionsFor($user);

        foreach ([
            UserPermissions::ACTION_APPROVE,
            UserPermissions::ACTION_UPDATE,
            UserPermissions::ACTION_DELETE,
            UserPermissions::ACTION_CREATE,
        ] as $action) {
            if (($permissions[$action] ?? []) !== []) {
                return true;
            }
        }

        return false;
    }
}
