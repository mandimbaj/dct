<?php

namespace App\Filament\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

/**
 * Main menu for country National Health Observatory customisation.
 *
 * This mirrors the legacy Django "National Observatory" admin module.
 */
class NationalObservatory extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static ?int $navigationSort = 8;

    protected static ?string $slug = 'national-observatory';

    public static function getNavigationLabel(): string
    {
        return __('aho.menus.national_observatory');
    }

    public static function getClusterBreadcrumb(): ?string
    {
        return __('aho.menus.national_observatory');
    }
}
