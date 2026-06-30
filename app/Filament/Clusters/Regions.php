<?php

namespace App\Filament\Clusters;

use App\Filament\AhoCluster;
use App\Support\AhoIcon;

/**
 * Main menu for locations and location levels.
 *
 * The user-facing label comes from the translation files; the class name remains Regions
 * because this was the original cluster name and route namespace.
 */
class Regions extends AhoCluster
{
    protected static ?string $ahoNavigationIcon = AhoIcon::REGIONS;

    protected static ?int $navigationSort = 8;

    protected static ?string $slug = 'regions';

    public static function getNavigationLabel(): string
    {
        return __('aho.menus.regions');
    }

    public static function getClusterBreadcrumb(): ?string
    {
        return __('aho.menus.regions');
    }
}
