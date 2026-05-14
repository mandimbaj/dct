<?php

namespace App\Filament\Resources\DataSources;

use App\Filament\Clusters\Indicators;
use App\Filament\Resources\Concerns\SearchesTranslatedRecords;
use App\Filament\Resources\DataSources\Pages\CreateDataSource;
use App\Filament\Resources\DataSources\Pages\EditDataSource;
use App\Filament\Resources\DataSources\Pages\ListDataSources;
use App\Filament\Resources\DataSources\Schemas\DataSourceForm;
use App\Filament\Resources\DataSources\Tables\DataSourcesTable;
use App\Models\DataSource;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class DataSourceResource extends Resource
{
    use SearchesTranslatedRecords;

    protected static ?string $model = DataSource::class;

    protected static ?string $cluster = Indicators::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static string|UnitEnum|null $navigationGroup = 'References';

    protected static ?string $slug = 'sources';

    protected static ?string $navigationLabel = 'Sources';

    protected static ?int $navigationSort = 6;

    protected static ?string $modelLabel = 'source';

    protected static ?string $pluralModelLabel = 'sources';

    protected static ?string $recordTitleAttribute = 'display_name';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.references');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.sources.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.sources.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.sources.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return DataSourceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DataSourcesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDataSources::route('/'),
            'create' => CreateDataSource::route('/create'),
            'edit' => EditDataSource::route('/{record}/edit'),
        ];
    }
}
