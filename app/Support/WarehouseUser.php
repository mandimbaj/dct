<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WarehouseUser
{
    public static function id(?User $user = null): int
    {
        $user ??= auth()->user();

        if (! $user) {
            return self::fallbackId();
        }

        $email = Str::lower(trim((string) $user->email));
        $query = DB::connection('warehouse')->table('authentication_customuser');
        $existingId = (clone $query)
            ->where('email', $email)
            ->orWhere('username', $email)
            ->value('id');

        if ($existingId) {
            return (int) $existingId;
        }

        [$firstName, $lastName] = self::splitName((string) $user->name);
        $now = now();

        $query->insertOrIgnore([
            'password' => '!laravel-managed-account',
            'last_login' => null,
            'is_superuser' => false,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'is_staff' => false,
            'is_active' => false,
            'date_joined' => $now,
            'title' => '',
            'gender' => '',
            'email' => $email,
            'postcode' => null,
            'username' => $email,
            'date_created' => $now,
            'date_lastupdated' => $now,
            'location_id' => $user->location_id,
        ]);

        return (int) DB::connection('warehouse')
            ->table('authentication_customuser')
            ->where('email', $email)
            ->value('id');
    }

    private static function fallbackId(): int
    {
        return (int) DB::connection('warehouse')
            ->table('authentication_customuser')
            ->orderBy('id')
            ->value('id');
    }

    /**
     * @return array{0: string, 1: string}
     */
    private static function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), 2) ?: [];

        return [
            Str::limit($parts[0] ?? '', 30, ''),
            Str::limit($parts[1] ?? '', 150, ''),
        ];
    }
}
