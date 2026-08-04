<?php

namespace App\Support;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Models\WarehouseAuthenticationUser;

class UserDisplayName
{
    public static function uploadedBy(?User $localUser, ?WarehouseAuthenticationUser $warehouseUser, mixed $userId = null): string
    {
        $localLabel = $localUser ? self::localLabel($localUser) : null;
        $warehouseLabel = $warehouseUser ? self::warehouseLabel($warehouseUser) : null;

        return $warehouseLabel
            ?? $localLabel
            ?? (filled($userId) ? __('aho.fields.user_id_fallback', ['id' => $userId]) : '-');
    }

    public static function uploadedByTooltip(?User $localUser, ?WarehouseAuthenticationUser $warehouseUser, mixed $userId = null): ?string
    {
        $parts = [];

        if ($warehouseUser) {
            $parts[] = __('aho.fields.django_user_label', [
                'user' => self::warehouseLabel($warehouseUser),
                'email' => $warehouseUser->email ?: 'N/A',
            ]);
        }

        if ($localUser) {
            $parts[] = __('aho.fields.local_user_label', [
                'user' => self::localLabel($localUser),
                'email' => $localUser->email ?: 'N/A',
            ]);
        }

        if ($parts === [] && filled($userId)) {
            $parts[] = __('aho.fields.user_id_fallback', ['id' => $userId]);
        }

        return $parts === [] ? null : implode(' | ', $parts);
    }

    public static function uploadedByWithFallback(
        ?User $localUser,
        ?WarehouseAuthenticationUser $warehouseUser,
        mixed $userId,
        ?User $fallbackLocalUser,
        ?WarehouseAuthenticationUser $fallbackWarehouseUser,
        mixed $fallbackUserId,
    ): string {
        if (self::shouldUseFallback($localUser, $warehouseUser, $userId, $fallbackLocalUser, $fallbackWarehouseUser, $fallbackUserId)) {
            return self::uploadedBy($fallbackLocalUser, $fallbackWarehouseUser, $fallbackUserId);
        }

        return self::uploadedBy($localUser, $warehouseUser, $userId);
    }

    public static function uploadedByTooltipWithFallback(
        ?User $localUser,
        ?WarehouseAuthenticationUser $warehouseUser,
        mixed $userId,
        ?User $fallbackLocalUser,
        ?WarehouseAuthenticationUser $fallbackWarehouseUser,
        mixed $fallbackUserId,
    ): ?string {
        if (self::shouldUseFallback($localUser, $warehouseUser, $userId, $fallbackLocalUser, $fallbackWarehouseUser, $fallbackUserId)) {
            return self::uploadedByTooltip($fallbackLocalUser, $fallbackWarehouseUser, $fallbackUserId);
        }

        return self::uploadedByTooltip($localUser, $warehouseUser, $userId);
    }

    private static function shouldUseFallback(
        ?User $localUser,
        ?WarehouseAuthenticationUser $warehouseUser,
        mixed $userId,
        ?User $fallbackLocalUser,
        ?WarehouseAuthenticationUser $fallbackWarehouseUser,
        mixed $fallbackUserId,
    ): bool {
        if (! filled($fallbackUserId) || (filled($userId) && (int) $fallbackUserId === (int) $userId)) {
            return false;
        }

        if (! $fallbackLocalUser && ! $fallbackWarehouseUser) {
            return false;
        }

        return self::isGenericSuperAdmin($localUser, $warehouseUser);
    }

    public static function canViewUploaders(): bool
    {
        $user = auth()->user();

        return (bool) $user && (bool) (
            $user->is_super_admin
            || $user->can_view_all_countries
            || $user->is_country_admin
            || UserPermissions::allowsResource($user, UserResource::class, UserPermissions::ACTION_VIEW)
        );
    }

    private static function localLabel(User $user): string
    {
        return trim((string) ($user->name ?: $user->email ?: '#'.$user->id));
    }

    private static function warehouseLabel(WarehouseAuthenticationUser $user): string
    {
        $name = trim(implode(' ', array_filter([
            $user->first_name ?? null,
            $user->last_name ?? null,
        ])));

        return $name
            ?: (string) ($user->username ?? null)
            ?: (string) ($user->email ?? null)
            ?: '#'.$user->id;
    }

    private static function isGenericSuperAdmin(?User $localUser, ?WarehouseAuthenticationUser $warehouseUser): bool
    {
        $warehouseLabel = $warehouseUser ? mb_strtolower(self::warehouseLabel($warehouseUser)) : '';
        $localLabel = $localUser ? mb_strtolower(self::localLabel($localUser)) : '';

        return $warehouseLabel === 'super admin'
            || $localLabel === 'super admin';
    }
}
