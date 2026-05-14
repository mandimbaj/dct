<?php

namespace App\Filament\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class DataWizard extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static ?int $navigationSort = 8;

    protected static ?string $slug = 'data-wizard';

    public static function getNavigationLabel(): string
    {
        return __('aho.menus.data_wizard');
    }

    public static function getClusterBreadcrumb(): ?string
    {
        return __('aho.menus.data_wizard');
    }
}
