<?php

namespace App\Filament\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

/**
 * Main menu for data element definitions, groups and country-scoped data element values.
 *
 * This module is separate from indicators because data elements are lower-level warehouse
 * inputs that can feed indicators or external integrations.
 */
class DataElements extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'data-elements';

    public static function getNavigationLabel(): string
    {
        return __('aho.menus.data_elements');
    }

    public static function getClusterBreadcrumb(): ?string
    {
        return __('aho.menus.data_elements');
    }
}
