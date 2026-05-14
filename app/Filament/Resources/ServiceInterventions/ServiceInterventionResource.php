<?php

namespace App\Filament\Resources\ServiceInterventions;

use App\Filament\Clusters\Facilities;
use App\Filament\Resources\Concerns\SearchesTranslatedRecords;
use App\Filament\Resources\ServiceInterventions\Pages\CreateServiceIntervention;
use App\Filament\Resources\ServiceInterventions\Pages\EditServiceIntervention;
use App\Filament\Resources\ServiceInterventions\Pages\ListServiceInterventions;
use App\Models\FacilityServiceIntervention;
use App\Support\FilamentSearch;
use App\Support\WarehouseForm;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ServiceInterventionResource extends Resource
{
    use SearchesTranslatedRecords;

    protected static ?string $model = FacilityServiceIntervention::class;

    protected static ?string $cluster = Facilities::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|UnitEnum|null $navigationGroup = 'References';

    protected static ?string $slug = 'service-interventions';

    protected static ?int $navigationSort = 9;

    protected static ?string $recordTitleAttribute = 'display_name';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.references');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.service_interventions.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.service_interventions.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.service_interventions.plural');
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
                        'domain' => ['code'],
                        'domain.translations' => ['name'],
                    ],
                    numericColumns: ['intervention_id'],
                );
            })
            ->columns([
                TextColumn::make('display_name')->label(__('aho.fields.service_intervention'))->wrap(),
                TextColumn::make('code')->label(__('aho.fields.code'))->searchable()->sortable(),
                TextColumn::make('domain.display_name')->label(__('aho.fields.service_domain'))->wrap()->toggleable(),
                TextColumn::make('service_areas_count')->label(__('aho.fields.service_areas_count'))->counts('serviceAreas')->sortable(),
                TextColumn::make('service_availabilities_count')->label(__('aho.fields.availability_count'))->counts('serviceAvailabilities')->sortable(),
                TextColumn::make('date_created')->label(__('aho.fields.creation'))->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('date_lastupdated')->label(__('aho.fields.modification'))->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('domain_id')
                    ->label(__('aho.fields.service_domain'))
                    ->relationship('domain', 'code')
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
            'index' => ListServiceInterventions::route('/'),
            'create' => CreateServiceIntervention::route('/create'),
            'edit' => EditServiceIntervention::route('/{record}/edit'),
        ];
    }
}
