<?php

namespace App\Filament\Clusters;

use App\Filament\AhoCluster;
use App\Support\AhoIcon;

/**
 * Main menu for UHC clock themes, groups, indicators and priority indicator values.
 *
 * Reference resources describe the clock structure; the priority-indicator resource stores
 * country-facing values for the dashboard-style clock experience.
 */
class UhcClock extends AhoCluster
{
    protected static ?string $ahoNavigationIcon = AhoIcon::UHC_CLOCK;

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'uhc-clock';

    public static function getNavigationLabel(): string
    {
        return __('aho.menus.uhc_clock');
    }

    public static function getClusterBreadcrumb(): ?string
    {
        return __('aho.menus.uhc_clock');
    }
}
