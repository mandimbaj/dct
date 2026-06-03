<?php

namespace App\Filament\Resources\IndicatorNarratives;

use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use App\Support\WarehouseForm;
use App\Filament\Resources\IndicatorNarratives\Pages\EditIndicatorNarrative;
use App\Filament\Resources\IndicatorNarratives\Pages\CreateIndicatorNarrative;
use App\Filament\Clusters\Indicators;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\Concerns\UsesFallbackResourcePermission;
use App\Filament\Resources\IndicatorNarratives\Pages\ListIndicatorNarratives;
use App\Filament\Resources\Indicators\IndicatorResource;
use App\Models\IndicatorNarrative;
use App\Support\FilamentSearch;
use App\Support\UserCountryAccess;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class IndicatorNarrativeResource extends Resource
{
    use UsesFallbackResourcePermission;

    protected static ?string $model = IndicatorNarrative::class;

    protected static ?string $cluster = Indicators::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'References';

    protected static ?string $slug = 'indicator-narratives';

    protected static ?int $navigationSort = 13;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.references');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.indicator_narratives.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.indicator_narratives.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.indicator_narratives.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return WarehouseForm::configure($schema, static::getModel());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('indicatornarrative_id', 'desc')
            ->searchUsing(function (Builder $query, string $search): void {
                FilamentSearch::apply(
                    query: $query,
                    search: $search,
                    columns: ['code', 'narrative_text'],
                    relations: [
                        'narrativeType.translations' => ['name'],
                        'indicator' => ['afrocode', 'gen_code'],
                        'indicator.translations' => ['name'],
                        'location.translations' => ['name'],
                    ],
                    numericColumns: ['indicatornarrative_id'],
                );
            })
            ->columns([
                TextColumn::make('indicatornarrative_id')->label(__('aho.fields.id'))->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('narrativeType.display_name')->label(__('aho.fields.type'))->wrap(),
                TextColumn::make('indicator.afrocode')->label(__('aho.fields.code'))->sortable(),
                TextColumn::make('indicator.display_name')->label(__('aho.fields.indicator'))->wrap(),
                TextColumn::make('location.display_name')->label(__('aho.fields.location'))->wrap(),
                TextColumn::make('narrative_text')->label(__('aho.fields.narrative_text'))->wrap()->limit(120),
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
        return UserCountryAccess::scope(parent::getEloquentQuery()->with([
            'narrativeType.translations',
            'indicator.translations',
            'location.translations',
        ]));
    }

    protected static function fallbackPermissionResources(): array
    {
        return [IndicatorResource::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIndicatorNarratives::route('/'),
            'create' => CreateIndicatorNarrative::route('/create'),
            'edit' => EditIndicatorNarrative::route('/{record}/edit'),
        ];
    }
}
