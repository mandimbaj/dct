<?php

namespace App\Filament\Clusters;

use App\Filament\AhoCluster;
use App\Support\AhoIcon;

/**
 * Main menu for indicator metadata, indicator fact values, archives and import/export records.
 *
 * Most resources in this cluster read the warehouse indicator tables and are country-scoped
 * when they expose fact rows.
 */
class Indicators extends AhoCluster
{
    protected static ?string $ahoNavigationIcon = AhoIcon::INDICATORS;

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'indicators';

    public static function getNavigationLabel(): string
    {
        return __('aho.menus.indicators');
    }

    public static function getClusterBreadcrumb(): ?string
    {
        return __('aho.menus.indicators');
    }
}
