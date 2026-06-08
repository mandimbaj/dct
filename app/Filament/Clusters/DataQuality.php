<?php

namespace App\Filament\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

/**
 * Main menu for data-quality checks and issue-resolution tables.
 *
 * These resources surface invalid references, missing values, consistency checks and failed
 * imports without changing the source fact tables directly.
 */
class DataQuality extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckBadge;

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
