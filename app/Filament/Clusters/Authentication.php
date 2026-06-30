<?php

namespace App\Filament\Clusters;

use App\Filament\AhoCluster;
use App\Support\AhoIcon;

/**
 * Main menu for users, roles, permissions and user page-visit history.
 *
 * Permissions are menu/action based and are enforced through App\Support\UserPermissions.
 */
class Authentication extends AhoCluster
{
    protected static ?string $ahoNavigationIcon = AhoIcon::AUTHENTICATION;

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
