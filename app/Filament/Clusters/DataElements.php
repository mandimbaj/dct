<?php

namespace App\Filament\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class DataElements extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'data-elements';

    public static function getNavigationLabel(): string
    {
        return __('aho.menus.data_elements');
    }

    public static function getClusterBreadcrumb(): ?string
    {
        return __('aho.menus.data_elements');
    }
}
