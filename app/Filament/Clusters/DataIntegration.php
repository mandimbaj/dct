<?php

namespace App\Filament\Clusters;

use App\Filament\AhoCluster;
use App\Support\AhoIcon;

/**
 * Main menu for external data integration connections and field mappings.
 *
 * Connection pages define provider settings, while the mapping page links incoming fields to
 * warehouse concepts.
 */
class DataIntegration extends AhoCluster
{
    protected static ?string $ahoNavigationIcon = AhoIcon::DATA_INTEGRATION;

    protected static ?int $navigationSort = 9;

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
