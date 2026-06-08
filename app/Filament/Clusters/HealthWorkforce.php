<?php

namespace App\Filament\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

/**
 * Main menu for workforce values, workforce reference tables and workforce publication subsets.
 *
 * See docs/health-workforce.md for the Django-to-Laravel mapping and warehouse table details.
 */
class HealthWorkforce extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'health-workforce';

    public static function getNavigationLabel(): string
    {
        return __('aho.menus.health_workforce');
    }

    public static function getClusterBreadcrumb(): ?string
    {
        return __('aho.menus.health_workforce');
    }
}
