<?php

namespace App\Filament\Clusters;

use App\Filament\AhoCluster;
use App\Support\AhoIcon;

/**
 * Main menu for knowledge products and publication reference data.
 *
 * Health workforce also exposes a filtered view of some publication models; keep global
 * publication behavior here unfiltered.
 */
class Publications extends AhoCluster
{
    protected static ?string $ahoNavigationIcon = AhoIcon::PUBLICATIONS;

    protected static ?int $navigationSort = 7;

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
