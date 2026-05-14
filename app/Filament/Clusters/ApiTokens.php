<?php

namespace App\Filament\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class ApiTokens extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'api-tokens';

    public static function getNavigationLabel(): string
    {
        return __('aho.menus.api_tokens');
    }

    public static function getClusterBreadcrumb(): ?string
    {
        return __('aho.menus.api_tokens');
    }
}
