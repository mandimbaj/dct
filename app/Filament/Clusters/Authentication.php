<?php

namespace App\Filament\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class Authentication extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?int $navigationSort = 11;

    protected static ?string $slug = 'authentication';

    public static function getNavigationLabel(): string
    {
        return __('aho.menus.authentication');
    }

    public static function getClusterBreadcrumb(): ?string
    {
        return __('aho.menus.authentication');
    }
}
