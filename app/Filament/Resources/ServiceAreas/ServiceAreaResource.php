<?php

namespace App\Filament\Resources\ServiceAreas;

use App\Filament\Clusters\Facilities;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\Concerns\SearchesTranslatedRecords;
use App\Filament\Resources\ServiceAreas\Pages\CreateServiceArea;
use App\Filament\Resources\ServiceAreas\Pages\EditServiceArea;
use App\Filament\Resources\ServiceAreas\Pages\ListServiceAreas;
use App\Models\FacilityServiceArea;
use App\Support\FilamentSearch;
use App\Support\WarehouseForm;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ServiceAreaResource extends Resource
{
    use SearchesTranslatedRecords;

    protected static ?string $model = FacilityServiceArea::class;

    protected static ?string $cluster = Facilities::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static string|UnitEnum|null $navigationGroup = 'References';

    protected static ?string $slug = 'service-areas';

    protected static ?int $navigationSort = 7;

    protected static ?string $recordTitleAttribute = 'display_name';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.references');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.service_areas.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.service_areas.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.service_areas.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return WarehouseForm::configure($schema, static::getModel());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->searchUsing(function (Builder $query, string $search): void {
                FilamentSearch::apply(
                    query: $query,
                    search: $search,
                    columns: ['code'],
                    relations: [
                        'translations' => ['name', 'shortname'],
                        'intervention' => ['code'],
                        'intervention.translations' => ['name'],
                        'intervention.domain' => ['code'],
                        'intervention.domain.translations' => ['name'],
                    ],
                    numericColumns: ['area_id'],
                );
            })
            ->columns([
                TextColumn::make('display_name')->label(__('aho.fields.service_area'))->wrap(),
                TextColumn::make('code')->label(__('aho.fields.code'))->searchable()->sortable(),
                TextColumn::make('intervention.display_name')->label(__('aho.fields.service_intervention'))->wrap()->toggleable(),
                TextColumn::make('intervention.domain.display_name')->label(__('aho.fields.service_domain'))->wrap()->toggleable(),
                TextColumn::make('service_availabilities_count')->label(__('aho.fields.availability_count'))->counts('serviceAvailabilities')->sortable(),
                TextColumn::make('date_created')->label(__('aho.fields.creation'))->dateTime()->sortable()->toggleable(),
                TextColumn::make('date_lastupdated')->label(__('aho.fields.modification'))->dateTime()->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('intervention_id')
                    ->label(__('aho.fields.service_intervention'))
                    ->relationship('intervention', 'code')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)
                    ->searchable(),
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

    public static function getPages(): array
    {
        return [
            'index' => ListServiceAreas::route('/'),
            'create' => CreateServiceArea::route('/create'),
            'edit' => EditServiceArea::route('/{record}/edit'),
        ];
    }
}
