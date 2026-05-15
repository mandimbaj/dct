<?php

namespace App\Filament\Resources\DqaFactsFilters;

use App\Filament\Clusters\DataQuality;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\DqaFactsFilters\Pages\ListDqaFactsFilters;
use App\Models\DataQuality\DqaFactsFilter;
use App\Support\DataQuality\DqaFilament;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class DqaFactsFilterResource extends Resource
{
    protected static ?string $model = DqaFactsFilter::class;

    protected static ?string $cluster = DataQuality::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?string $slug = 'facts-filter';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return null;
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.dqa_facts_filter.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.dqa_facts_filter.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.dqa_facts_filter.plural');
    }

    public static function table(Table $table): Table
    {
        return DqaFilament::factsFilterTable($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDqaFactsFilters::route('/'),
        ];
    }
}
