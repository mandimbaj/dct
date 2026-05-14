<?php

namespace App\Filament\Resources\LocationLevels;

use App\Filament\Clusters\Regions;
use App\Filament\Resources\Concerns\SearchesTranslatedRecords;
use App\Filament\Resources\LocationLevels\Pages\CreateLocationLevel;
use App\Filament\Resources\LocationLevels\Pages\EditLocationLevel;
use App\Filament\Resources\LocationLevels\Pages\ListLocationLevels;
use App\Filament\Resources\LocationLevels\Schemas\LocationLevelForm;
use App\Filament\Resources\LocationLevels\Tables\LocationLevelsTable;
use App\Models\LocationLevel;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class LocationLevelResource extends Resource
{
    use SearchesTranslatedRecords;

    protected static ?string $model = LocationLevel::class;

    protected static ?string $cluster = Regions::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static string|UnitEnum|null $navigationGroup = 'References';

    protected static ?string $slug = 'levels';

    protected static ?string $navigationLabel = 'Location levels';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'location level';

    protected static ?string $pluralModelLabel = 'location levels';

    protected static ?string $recordTitleAttribute = 'display_name';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.references');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.location_levels.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.location_levels.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.location_levels.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return LocationLevelForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LocationLevelsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLocationLevels::route('/'),
            'create' => CreateLocationLevel::route('/create'),
            'edit' => EditLocationLevel::route('/{record}/edit'),
        ];
    }
}
