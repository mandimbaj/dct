<?php

namespace App\Filament\Resources\Concerns;

use App\Support\UserPermissions;

/**
 * Gives a new Filament resource access through one or more existing resource permissions.
 *
 * This keeps legacy roles usable after a menu is split into smaller submenus. For example,
 * a user who could already see Health workforce values can also see related workforce
 * event submenus without an immediate role reconfiguration.
 */
trait UsesFallbackResourcePermission
{
    /**
     * Check direct access first, then the resource classes returned by fallbackPermissionResources().
     */
    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->is_super_admin) {
            return true;
        }

        foreach ([static::class, ...static::fallbackPermissionResources()] as $resourceClass) {
            if (UserPermissions::allowsResource($user, $resourceClass, UserPermissions::ACTION_VIEW)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Filament calls this for list pages; keep it aligned with canAccess().
     */
    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    /**
     * Override in the resource when a new submenu should inherit visibility from an older one.
     *
     * @return array<int, class-string>
     */
    protected static function fallbackPermissionResources(): array
    {
        return [];
    }
}
