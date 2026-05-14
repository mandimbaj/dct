<?php

namespace App\Filament\Resources\Countries;

use App\Filament\Clusters\Regions;
use App\Filament\Resources\Countries\Pages\CreateCountry;
use App\Filament\Resources\Countries\Pages\EditCountry;
use App\Filament\Resources\Countries\Pages\ListCountries;
use App\Filament\Resources\Countries\Schemas\CountryForm;
use App\Filament\Resources\Countries\Tables\CountriesTable;
use App\Models\Country;
use App\Support\UserCountryAccess;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CountryResource extends Resource
{
    protected static ?string $model = Country::class;

    protected static ?string $cluster = Regions::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeEuropeAfrica;

    protected static string|UnitEnum|null $navigationGroup = 'References';

    protected static ?string $slug = 'locations';

    protected static ?string $navigationLabel = 'Locations';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'location';

    protected static ?string $pluralModelLabel = 'locations';

    protected static ?string $recordTitleAttribute = 'display_name';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.references');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.locations.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.locations.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.locations.plural');
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
            'locationLevel.code',
            'locationLevel.translations.name',
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
        return UserCountryAccess::scope(
            parent::getEloquentQuery()->with(['translations', 'parent.translations', 'locationLevel.translations']),
            'location_id',
        );
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
            'index' => ListCountries::route('/'),
            'create' => CreateCountry::route('/create'),
            'edit' => EditCountry::route('/{record}/edit'),
        ];
    }
}
