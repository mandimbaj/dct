<?php

namespace App\Filament\Resources\DqaFactsDatasets;

use App\Filament\Clusters\DataQuality;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\DqaFactsDatasets\Pages\ListDqaFactsDatasets;
use App\Models\DataQuality\DqaFactsDataset;
use App\Support\DataQuality\DqaFilament;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class DqaFactsDatasetResource extends Resource
{
    protected static ?string $model = DqaFactsDataset::class;

    protected static ?string $cluster = DataQuality::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?string $slug = 'facts-dataset';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return null;
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.dqa_facts_dataset.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.dqa_facts_dataset.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.dqa_facts_dataset.plural');
    }

    public static function table(Table $table): Table
    {
        return DqaFilament::factsDatasetTable($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return DqaFilament::scopeFactsDatasetQuery(parent::getEloquentQuery());
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDqaFactsDatasets::route('/'),
        ];
    }
}
