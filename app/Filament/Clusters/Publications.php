<?php

namespace App\Filament\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class Publications extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'publications';

    public static function getNavigationLabel(): string
    {
        return __('aho.menus.publications');
    }

    public static function getClusterBreadcrumb(): ?string
    {
        return __('aho.menus.publications');
    }
}
