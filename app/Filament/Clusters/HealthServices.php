<?php

namespace App\Filament\Clusters;

use App\Filament\AhoCluster;
use App\Support\AhoIcon;

/**
 * Main menu for health-service fact values.
 *
 * The current cluster is intentionally narrow: it exposes service values while reusing
 * indicator, source, method and location references from the shared warehouse models.
 */
class HealthServices extends AhoCluster
{
    protected static ?string $ahoNavigationIcon = AhoIcon::HEALTH_SERVICES;

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
