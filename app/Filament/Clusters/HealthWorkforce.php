<?php

namespace App\Filament\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class HealthWorkforce extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'health-workforce';

    public static function getNavigationLabel(): string
    {
        return __('aho.menus.health_workforce');
    }

    public static function getClusterBreadcrumb(): ?string
    {
        return __('aho.menus.health_workforce');
    }
}
