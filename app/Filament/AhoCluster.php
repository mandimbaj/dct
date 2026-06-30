<?php

namespace App\Filament;

use App\Support\AhoIcon;
use BackedEnum;
use Filament\Clusters\Cluster;
use Illuminate\Contracts\Support\Htmlable;

abstract class AhoCluster extends Cluster
{
    protected static ?string $ahoNavigationIcon = null;

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        if (filled(static::$ahoNavigationIcon)) {
            return AhoIcon::make(static::$ahoNavigationIcon);
        }

        return parent::getNavigationIcon();
    }
}
