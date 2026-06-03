<?php

namespace App\Filament\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

/**
 * Main menu for knowledge products and publication reference data.
 *
 * Health workforce also exposes a filtered view of some publication models; keep global
 * publication behavior here unfiltered.
 */
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
