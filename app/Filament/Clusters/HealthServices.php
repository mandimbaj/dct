<?php

namespace App\Filament\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

/**
 * Main menu for health-service fact values.
 *
 * The current cluster is intentionally narrow: it exposes service values while reusing
 * indicator, source, method and location references from the shared warehouse models.
 */
class HealthServices extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'health-services';

    public static function getNavigationLabel(): string
    {
        return __('aho.menus.health_services');
    }

    public static function getClusterBreadcrumb(): ?string
    {
        return __('aho.menus.health_services');
    }
}
