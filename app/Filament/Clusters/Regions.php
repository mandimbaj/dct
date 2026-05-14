<?php

namespace App\Filament\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class Regions extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeEuropeAfrica;

    protected static ?int $navigationSort = 7;

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
