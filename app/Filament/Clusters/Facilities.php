<?php

namespace App\Filament\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

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
