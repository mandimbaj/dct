<?php

namespace App\Filament\Resources\UhcCountrySelections;

use App\Filament\Clusters\UhcClock;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\Concerns\UsesFallbackResourcePermission;
use App\Filament\Resources\UhcCountrySelections\Pages\CreateUhcCountrySelection;
use App\Filament\Resources\UhcCountrySelections\Pages\EditUhcCountrySelection;
use App\Filament\Resources\UhcCountrySelections\Pages\ListUhcCountrySelections;
use App\Filament\Resources\UhcPriorityIndicators\UhcPriorityIndicatorResource;
use App\Models\UhcCountrySelection;
use App\Support\FilamentSearch;
use App\Support\TranslatedReferenceForm;
use App\Support\UserCountryAccess;
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

class UhcCountrySelectionResource extends Resource
{
    use UsesFallbackResourcePermission;

    protected static ?string $model = UhcCountrySelection::class;

    protected static ?string $cluster = UhcClock::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|UnitEnum|null $navigationGroup = 'References';

    protected static ?string $slug = 'country-selections';

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.references');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.uhc_country_selections.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.uhc_country_selections.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.uhc_country_selections.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return TranslatedReferenceForm::uhcCountrySelection($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('countrychoice_id')
            ->searchUsing(function (Builder $query, string $search): void {
                FilamentSearch::apply(
                    query: $query,
                    search: $search,
                    relations: ['location.translations' => ['name']],
                    numericColumns: ['countrychoice_id', 'themes_count', 'indicators_count'],
                );
            })
            ->columns([
                TextColumn::make('countrychoice_id')->label(__('aho.fields.id'))->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('location.display_name')->label(__('aho.fields.location'))->wrap(),
                TextColumn::make('themes_count')->label(__('aho.fields.themes'))->sortable(),
                TextColumn::make('indicators_count')->label(__('aho.fields.indicators'))->sortable(),
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
        return UserCountryAccess::scope(parent::getEloquentQuery()
            ->with('location.translations')
            ->withCount(['themes', 'indicators']));
    }

    protected static function fallbackPermissionResources(): array
    {
        return [UhcPriorityIndicatorResource::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUhcCountrySelections::route('/'),
            'create' => CreateUhcCountrySelection::route('/create'),
            'edit' => EditUhcCountrySelection::route('/{record}/edit'),
        ];
    }
}
