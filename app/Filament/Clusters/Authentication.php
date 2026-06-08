<?php

namespace App\Filament\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

/**
 * Main menu for users, roles, permissions and user page-visit history.
 *
 * Permissions are menu/action based and are enforced through App\Support\UserPermissions.
 */
class Authentication extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?int $navigationSort = 12;

    protected static ?string $slug = 'authentication';

    public static function getNavigationLabel(): string
    {
        return __('aho.menus.authentication');
    }

    public static function getClusterBreadcrumb(): ?string
    {
        return __('aho.menus.authentication');
    }
}
