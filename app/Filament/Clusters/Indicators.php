<?php

namespace App\Filament\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

/**
 * Main menu for indicator metadata, indicator fact values, archives and import/export records.
 *
 * Most resources in this cluster read the warehouse indicator tables and are country-scoped
 * when they expose fact rows.
 */
class Indicators extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

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
