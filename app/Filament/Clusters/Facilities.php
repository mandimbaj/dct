<?php

namespace App\Filament\Clusters;

use App\Filament\AhoCluster;
use App\Support\AhoIcon;

/**
 * Main menu for facility master data and facility service measurements.
 *
 * Data resources hold facility records and service availability/capacity/readiness facts;
 * reference resources hold the service taxonomy used by those facts.
 */
class Facilities extends AhoCluster
{
    protected static ?string $ahoNavigationIcon = AhoIcon::FACILITIES;

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'facilities';

    public static function getNavigationLabel(): string
    {
        return __('aho.menus.facilities');
    }

    public static function getClusterBreadcrumb(): ?string
    {
        return __('aho.menus.facilities');
    }
}
