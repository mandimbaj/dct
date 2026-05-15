<?php

namespace App\Filament\Resources\DqaValidDataSources;

use App\Filament\Clusters\DataQuality;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\DqaValidDataSources\Pages\ListDqaValidDataSources;
use App\Models\DataQuality\DqaValidDataSource;
use App\Support\DataQuality\DqaFilament;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class DqaValidDataSourceResource extends Resource
{
    protected static ?string $model = DqaValidDataSource::class;

    protected static ?string $cluster = DataQuality::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?string $slug = 'datasources';

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return null;
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.dqa_valid_data_sources.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.dqa_valid_data_sources.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.dqa_valid_data_sources.plural');
    }

    public static function table(Table $table): Table
    {
        return DqaFilament::lookupTable($table, static::getModel(), [
            'id' => 'id',
            'afrocode' => 'afro_code',
            'indicator_id' => 'indicator_id',
            'datasource_id' => 'source',
            'datasourceid' => 'source_id',
            'user_id' => 'user',
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDqaValidDataSources::route('/'),
        ];
    }
}
