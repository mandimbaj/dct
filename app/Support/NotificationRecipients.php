<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class NotificationRecipients
{
    /**
     * @return Collection<int, User>
     */
    public static function forCountry(?int $locationId, ?int $exceptUserId = null): Collection
    {
        $users = User::query()
            ->where(function ($query) use ($locationId): void {
                $query->where('is_super_admin', true);

                if (filled($locationId)) {
                    $query
                        ->orWhere('location_id', $locationId)
                        ->orWhereHas('locationAssignments', fn ($query) => $query->where('country_location_id', $locationId));
                }
            })
            ->when($exceptUserId, fn ($query) => $query->whereKeyNot($exceptUserId))
            ->get();

        return $users->unique('id')->values();
    }
}
