<?php

namespace App\Filament\Clusters;

use App\Filament\AhoCluster;
use App\Support\AhoIcon;

/**
 * Main menu for data-quality checks and issue-resolution tables.
 *
 * These resources surface invalid references, missing values, consistency checks and failed
 * imports without changing the source fact tables directly.
 */
class DataQuality extends AhoCluster
{
    protected static ?string $ahoNavigationIcon = AhoIcon::DATA_QUALITY;

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'data-quality';

    public static function getNavigationLabel(): string
    {
        return __('aho.menus.data_quality');
    }

    public static function getClusterBreadcrumb(): ?string
    {
        return __('aho.menus.data_quality');
    }
}
