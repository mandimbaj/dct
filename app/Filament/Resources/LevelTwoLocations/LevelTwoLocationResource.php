<?php

namespace App\Filament\Resources\LevelTwoLocations;

use App\Filament\Clusters\Regions;
use App\Filament\Resources\Countries\Schemas\CountryForm;
use App\Filament\Resources\Countries\Tables\CountriesTable;
use App\Filament\Resources\LevelTwoLocations\Pages\CreateLevelTwoLocation;
use App\Filament\Resources\LevelTwoLocations\Pages\EditLevelTwoLocation;
use App\Filament\Resources\LevelTwoLocations\Pages\ListLevelTwoLocations;
use App\Models\Country;
use App\Support\UserCountryAccess;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class LevelTwoLocationResource extends Resource
{
    protected static ?string $model = Country::class;

    protected static ?string $cluster = Regions::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|UnitEnum|null $navigationGroup = 'References';

    protected static ?string $slug = 'level-2-locations';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'display_name';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.references');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.level_two_locations.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.level_two_locations.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.level_two_locations.plural');
    }

    /**
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return [
            'code',
            'iso_alpha',
            'iso_number',
            'translations.name',
            'parent.code',
            'parent.translations.name',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return CountryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CountriesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['translations', 'parent.translations', 'locationLevel.translations'])
            ->whereHas('parent', fn (Builder $query): Builder => $query->where('locationlevel_id', 2));

        return UserCountryAccess::scope($query, 'location_id');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLevelTwoLocations::route('/'),
            'create' => CreateLevelTwoLocation::route('/create'),
            'edit' => EditLevelTwoLocation::route('/{record}/edit'),
        ];
    }
}
