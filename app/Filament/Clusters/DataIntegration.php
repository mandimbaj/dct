<?php

namespace App\Filament\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

/**
 * Main menu for external data integration connections and field mappings.
 *
 * Connection pages define provider settings, while the mapping page links incoming fields to
 * warehouse concepts.
 */
class DataIntegration extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static ?int $navigationSort = 8;

    protected static ?string $slug = 'data-integration';

    public static function getNavigationLabel(): string
    {
        return __('aho.menus.data_integration');
    }

    public static function getClusterBreadcrumb(): ?string
    {
        return __('aho.menus.data_integration');
    }
}
