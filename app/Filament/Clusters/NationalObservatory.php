<?php

namespace App\Filament\Clusters;

use App\Filament\AhoCluster;
use App\Support\AhoIcon;

/**
 * Main menu for country National Health Observatory customisation.
 *
 * This mirrors the legacy Django "National Observatory" admin module.
 */
class NationalObservatory extends AhoCluster
{
    protected static ?string $ahoNavigationIcon = AhoIcon::NATIONAL_OBSERVATORY;

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
