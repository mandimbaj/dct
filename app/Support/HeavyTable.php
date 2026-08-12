<?php

namespace App\Support;

use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class HeavyTable
{
    /**
     * @var array<int, int>
     */
    public const PAGE_OPTIONS = [10, 25, 50];

    public static function configure(Table $table): Table
    {
        return $table
            ->paginationMode(PaginationMode::Simple)
            ->paginationPageOptions(self::PAGE_OPTIONS)
            ->defaultPaginationPageOption(10);
    }

    /**
     * @param  array<int, string>  $relations
     */
    public static function withUploadersWhenAllowed(
        Builder $query,
        array $relations = ['uploadedBy', 'warehouseUploadedBy'],
    ): Builder {
        if (! UserDisplayName::canViewUploaders()) {
            return $query;
        }

        return $query->with($relations);
    }
}
