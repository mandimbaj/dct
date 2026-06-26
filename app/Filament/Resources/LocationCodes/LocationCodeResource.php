<?php

namespace App\Filament\Resources\LocationCodes;

use App\Filament\Clusters\Regions;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\Concerns\UsesFallbackResourcePermission;
use App\Filament\Resources\Countries\CountryResource;
use App\Filament\Resources\LocationCodes\Pages\CreateLocationCode;
use App\Filament\Resources\LocationCodes\Pages\EditLocationCode;
use App\Filament\Resources\LocationCodes\Pages\ListLocationCodes;
use App\Models\LocationCode;
use App\Support\FilamentSearch;
use App\Support\TranslatedReferenceForm;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class LocationCodeResource extends Resource
{
    use UsesFallbackResourcePermission;

    protected static ?string $model = LocationCode::class;

    protected static ?string $cluster = Regions::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|UnitEnum|null $navigationGroup = 'References';

    protected static ?string $slug = 'dial-codes';

    protected static ?int $navigationSort = 7;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.references');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.location_codes.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.location_codes.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.location_codes.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return TranslatedReferenceForm::locationCode($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('location_id')
            ->searchUsing(function (Builder $query, string $search): void {
                FilamentSearch::apply(
                    query: $query,
                    search: $search,
                    columns: ['country_code'],
                    relations: ['location.translations' => ['name']],
                    numericColumns: ['location_id'],
                );
            })
            ->columns([
                TextColumn::make('location_id')->label(__('aho.fields.id'))->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('location.display_name')->label(__('aho.fields.location'))->sortable()->wrap(),
                TextColumn::make('country_code')->label(__('aho.fields.dial_code'))->sortable(),
                TextColumn::make('date_created')->label(__('aho.fields.creation'))->dateTime()->sortable()->toggleable(),
                TextColumn::make('date_lastupdated')->label(__('aho.fields.modification'))->dateTime()->sortable()->toggleable(),
            ])
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('location.translations');
    }

    protected static function fallbackPermissionResources(): array
    {
        return [CountryResource::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLocationCodes::route('/'),
            'create' => CreateLocationCode::route('/create'),
            'edit' => EditLocationCode::route('/{record}/edit'),
        ];
    }
}
