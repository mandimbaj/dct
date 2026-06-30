<?php

namespace App\Filament\Clusters;

use App\Filament\AhoCluster;
use App\Support\AhoIcon;

/**
 * Main menu for workforce values, workforce reference tables and workforce publication subsets.
 *
 * See docs/health-workforce.md for the Django-to-Laravel mapping and warehouse table details.
 */
class HealthWorkforce extends AhoCluster
{
    protected static ?string $ahoNavigationIcon = AhoIcon::HEALTH_WORKFORCE;

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
