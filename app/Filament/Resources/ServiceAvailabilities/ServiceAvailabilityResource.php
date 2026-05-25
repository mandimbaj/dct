<?php

namespace App\Filament\Resources\ServiceAvailabilities;

use App\Filament\Clusters\Facilities;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\Concerns\ScopesFacilityCountryAccess;
use App\Filament\Resources\ServiceAvailabilities\Pages\CreateServiceAvailability;
use App\Filament\Resources\ServiceAvailabilities\Pages\EditServiceAvailability;
use App\Filament\Resources\ServiceAvailabilities\Pages\ListServiceAvailabilities;
use App\Models\FacilityServiceArea;
use App\Models\FacilityServiceAvailability;
use App\Models\FacilityServiceDomain;
use App\Models\FacilityServiceIntervention;
use App\Models\HealthFacility;
use App\Support\FilamentSearch;
use App\Support\SelectOptions;
use App\Support\UserCountryAccess;
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

class ServiceAvailabilityResource extends Resource
{
    use ScopesFacilityCountryAccess;

    protected static ?string $model = FacilityServiceAvailability::class;

    protected static ?string $cluster = Facilities::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Data';

    protected static ?string $slug = 'service-availability';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'display_name';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.data');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.service_availability.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.service_availability.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.service_availability.plural');
    }

    /**
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return [
            'code',
            'facility.name',
            'facility.code',
            'domain.translations.name',
            'intervention.translations.name',
            'serviceArea.translations.name',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return WarehouseForm::configure($schema, static::getModel());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('availability_id', 'desc')
            ->searchUsing(function (Builder $query, string $search): void {
                FilamentSearch::apply(
                    query: $query,
                    search: $search,
                    columns: ['code'],
                    relations: [
                        'facility' => ['name', 'shortname', 'code', 'admin_location'],
                        'domain' => ['code'],
                        'domain.translations' => ['name'],
                        'intervention' => ['code'],
                        'intervention.translations' => ['name'],
                        'serviceArea' => ['code'],
                        'serviceArea.translations' => ['name'],
                    ],
                    numericColumns: ['availability_id'],
                );
            })
            ->columns([
                TextColumn::make('availability_id')->label(__('aho.fields.id'))->sortable()->toggleable(),
                TextColumn::make('facility.display_name')->label(__('aho.fields.facility'))->wrap()->toggleable(),
                TextColumn::make('domain.display_name')->label(__('aho.fields.service_domain'))->wrap()->toggleable(),
                TextColumn::make('intervention.display_name')->label(__('aho.fields.service_intervention'))->wrap()->toggleable(),
                TextColumn::make('serviceArea.display_name')->label(__('aho.fields.service_area'))->wrap()->toggleable(),
                static::booleanColumn('provided', __('aho.fields.provided')),
                static::booleanColumn('specialunit', __('aho.fields.specialunit'))->toggleable(isToggledHiddenByDefault: true),
                static::booleanColumn('staff', __('aho.fields.staff'))->toggleable(isToggledHiddenByDefault: true),
                static::booleanColumn('infrastructure', __('aho.fields.infrastructure'))->toggleable(isToggledHiddenByDefault: true),
                static::booleanColumn('supplies', __('aho.fields.supplies'))->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('date_assessed')->label(__('aho.fields.date_assessed'))->date()->sortable(),
                TextColumn::make('date_created')->label(__('aho.fields.creation'))->dateTime()->sortable()->toggleable(),
                TextColumn::make('date_lastupdated')->label(__('aho.fields.modification'))->dateTime()->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('facility_id')
                    ->label(__('aho.fields.facility'))
                    ->relationship('facility', 'name', modifyQueryUsing: fn (Builder $query): Builder => UserCountryAccess::scopeLocations(
                        SelectOptions::orderByDisplayName($query, 'name'),
                    ))
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)
                    ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::fromDisplayNameQuery(
                        UserCountryAccess::scopeLocations(HealthFacility::query()),
                        $search,
                        'facility_id',
                    ))
                    ->searchable(),
                SelectFilter::make('domain_id')
                    ->label(__('aho.fields.service_domain'))
                    ->relationship('domain', 'code', modifyQueryUsing: fn (Builder $query): Builder => SelectOptions::orderByDisplayName($query, 'code'))
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)
                    ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::fromDisplayNameQuery(FacilityServiceDomain::query(), $search, 'domain_id'))
                    ->searchable(),
                SelectFilter::make('intervention_id')
                    ->label(__('aho.fields.service_intervention'))
                    ->relationship('intervention', 'code', modifyQueryUsing: fn (Builder $query): Builder => SelectOptions::orderByDisplayName($query, 'code'))
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)
                    ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::fromDisplayNameQuery(FacilityServiceIntervention::query(), $search, 'intervention_id'))
                    ->searchable(),
                SelectFilter::make('service_id')
                    ->label(__('aho.fields.service_area'))
                    ->relationship('serviceArea', 'code', modifyQueryUsing: fn (Builder $query): Builder => SelectOptions::orderByDisplayName($query, 'code'))
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)
                    ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::fromDisplayNameQuery(FacilityServiceArea::query(), $search, 'area_id'))
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

    private static function booleanColumn(string $name, string $label): TextColumn
    {
        return TextColumn::make($name)
            ->label($label)
            ->formatStateUsing(fn ($state): string => $state ? __('aho.fields.yes') : __('aho.fields.no'))
            ->badge();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListServiceAvailabilities::route('/'),
            'create' => CreateServiceAvailability::route('/create'),
            'edit' => EditServiceAvailability::route('/{record}/edit'),
        ];
    }
}
