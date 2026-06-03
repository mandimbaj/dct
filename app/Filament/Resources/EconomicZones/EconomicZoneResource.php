<?php

namespace App\Filament\Resources\EconomicZones;

use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use App\Support\TranslatedReferenceForm;
use App\Filament\Resources\EconomicZones\Pages\EditEconomicZone;
use App\Filament\Resources\EconomicZones\Pages\CreateEconomicZone;
use App\Filament\Clusters\Regions;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\Concerns\UsesFallbackResourcePermission;
use App\Filament\Resources\Countries\CountryResource;
use App\Filament\Resources\EconomicZones\Pages\ListEconomicZones;
use App\Models\EconomicZone;
use App\Support\FilamentReadOnlyTables;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class EconomicZoneResource extends Resource
{
    use UsesFallbackResourcePermission;

    protected static ?string $model = EconomicZone::class;

    protected static ?string $cluster = Regions::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeEuropeAfrica;

    protected static string|UnitEnum|null $navigationGroup = 'References';

    protected static ?string $slug = 'economic-zones';

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.references');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.economic_zones.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.economic_zones.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.economic_zones.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return TranslatedReferenceForm::configure($schema, static::getModel());
    }

    public static function table(Table $table): Table
    {
        return FilamentReadOnlyTables::translatedReference($table, 'economiczone_id', 'economic_zone')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected static function fallbackPermissionResources(): array
    {
        return [CountryResource::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEconomicZones::route('/'),
            'create' => CreateEconomicZone::route('/create'),
            'edit' => EditEconomicZone::route('/{record}/edit'),
        ];
    }
}
