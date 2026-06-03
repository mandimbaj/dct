<?php

namespace App\Filament\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

/**
 * Main menu for facility master data and facility service measurements.
 *
 * Data resources hold facility records and service availability/capacity/readiness facts;
 * reference resources hold the service taxonomy used by those facts.
 */
class Facilities extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?int $navigationSort = 2;

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
