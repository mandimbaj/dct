<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class WarehouseUserSynchronizer
{
    /**
     * @return array{total: int, created: int, matched: int}
     */
    public function syncIfDue(?int $locationId = null): array
    {
        return Cache::remember(
            $this->cacheKey($locationId),
            now()->addMinutes(10),
            fn (): array => $this->sync($locationId),
        );
    }

    /**
     * @return array{total: int, created: int, matched: int}
     */
    public function sync(?int $locationId = null): array
    {
        $warehouseUsers = DB::connection('warehouse')
            ->table('authentication_customuser')
            ->select([
                'id',
                'first_name',
                'last_name',
                'email',
                'username',
                'is_active',
                'last_login',
                'location_id',
            ])
            ->when($locationId !== null, fn ($query) => $query->where('location_id', $locationId))
            ->orderBy('id')
            ->get();

        $localUsers = User::query()
            ->get()
            ->keyBy(fn (User $user): string => Str::lower(trim((string) $user->email)));
        $unusablePassword = Hash::make(Str::random(64));
        $created = 0;
        $matched = 0;

        DB::transaction(function () use ($warehouseUsers, $localUsers, $unusablePassword, &$created, &$matched): void {
            foreach ($warehouseUsers as $warehouseUser) {
                $email = Str::lower(trim((string) $warehouseUser->email));

                if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }

                /** @var User|null $localUser */
                $localUser = $localUsers->get($email);

                if ($localUser) {
                    $matched++;

                    continue;
                }

                $localUser = new User;
                $localUser->forceFill([
                    'name' => $this->displayName($warehouseUser),
                    'email' => $email,
                    'password' => $unusablePassword,
                    'email_verified_at' => null,
                    'is_super_admin' => false,
                    'can_view_all_countries' => false,
                    'is_country_admin' => false,
                    'location_id' => $warehouseUser->location_id,
                    'role_id' => null,
                    'menu_permissions' => null,
                ])->saveQuietly();

                $localUsers->put($email, $localUser);
                $created++;
            }
        });

        $summary = [
            'total' => $warehouseUsers->count(),
            'created' => $created,
            'matched' => $matched,
        ];

        Cache::put($this->cacheKey($locationId), $summary, now()->addMinutes(10));

        return $summary;
    }

    private function displayName(object $warehouseUser): string
    {
        $name = trim(implode(' ', array_filter([
            $warehouseUser->first_name,
            $warehouseUser->last_name,
        ])));

        return Str::limit($name !== '' ? $name : (string) $warehouseUser->username, 255, '');
    }

    private function cacheKey(?int $locationId): string
    {
        return 'warehouse-users.sync.'.($locationId ?? 'all');
    }
}
