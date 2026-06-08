<?php

namespace App\Filament\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

/**
 * Main menu for UHC clock themes, groups, indicators and priority indicator values.
 *
 * Reference resources describe the clock structure; the priority-indicator resource stores
 * country-facing values for the dashboard-style clock experience.
 */
class UhcClock extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

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
