<?php

namespace App\Filament\Clusters;

use App\Filament\AhoCluster;
use App\Support\AhoIcon;

/**
 * Main menu for API token status and token lifecycle actions.
 *
 * This cluster is page-based instead of resource-based because token creation/revocation is
 * a focused workflow rather than a general CRUD table.
 */
class ApiTokens extends AhoCluster
{
    protected static ?string $ahoNavigationIcon = AhoIcon::API_TOKENS;

    protected static ?int $navigationSort = 11;

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
