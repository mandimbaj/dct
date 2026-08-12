<?php

namespace App\Filament\Resources\ServiceReadiness;

use App\Filament\Clusters\Facilities;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\Concerns\ScopesFacilityCountryAccess;
use App\Filament\Resources\ServiceReadiness\Pages\CreateServiceReadiness;
use App\Filament\Resources\ServiceReadiness\Pages\EditServiceReadiness;
use App\Filament\Resources\ServiceReadiness\Pages\ListServiceReadiness;
use App\Models\FacilityProvisionUnit;
use App\Models\FacilityServiceDomain;
use App\Models\FacilityServiceReadiness;
use App\Models\HealthFacility;
use App\Support\FilamentSearch;
use App\Support\HeavyTable;
use App\Support\SelectOptions;
use App\Support\UserCountryAccess;
use App\Support\UserDisplayName;
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

class ServiceReadinessResource extends Resource
{
    use ScopesFacilityCountryAccess;

    protected static ?string $model = FacilityServiceReadiness::class;

    protected static ?string $cluster = Facilities::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;

    protected static string|UnitEnum|null $navigationGroup = 'Data';

    protected static ?string $slug = 'service-readiness';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'display_name';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.data');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.service_readiness.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.service_readiness.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.service_readiness.plural');
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
            'unit.translations.name',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return WarehouseForm::configure($schema, static::getModel());
    }

    public static function table(Table $table): Table
    {
        return HeavyTable::configure($table)
            ->defaultSort('readiness_id', 'desc')
            ->searchUsing(function (Builder $query, string $search): void {
                FilamentSearch::apply(
                    query: $query,
                    search: $search,
                    columns: ['code'],
                    relations: [
                        'facility' => ['name', 'shortname', 'code', 'admin_location'],
                        'domain' => ['code'],
                        'domain.translations' => ['name'],
                        'unit' => ['code'],
                        'unit.translations' => ['name'],
                    ],
                    numericColumns: ['readiness_id', 'available', 'require'],
                );
            })
            ->columns([
                TextColumn::make('readiness_id')->label(__('aho.fields.id'))->sortable()->toggleable(),
                TextColumn::make('facility.display_name')->label(__('aho.fields.facility'))->wrap()->toggleable(),
                TextColumn::make('domain.display_name')->label(__('aho.fields.service_domain'))->wrap()->toggleable(),
                TextColumn::make('unit.display_name')->label(__('aho.fields.provision_unit'))->wrap()->toggleable(),
                TextColumn::make('available')->label(__('aho.fields.available'))->numeric()->sortable(),
                TextColumn::make('require')->label(__('aho.fields.required'))->numeric()->sortable(),
                TextColumn::make('date_assessed')->label(__('aho.fields.date_assessed'))->date()->sortable(),
                TextColumn::make('date_created')->label(__('aho.fields.creation'))->dateTime()->sortable()->toggleable(),
                TextColumn::make('date_lastupdated')->label(__('aho.fields.modification'))->dateTime()->sortable()->toggleable(),
                TextColumn::make('uploadedBy.name')
                    ->label(__('aho.fields.uploaded_by'))
                    ->state(fn (FacilityServiceReadiness $record): string => UserDisplayName::uploadedBy(
                        $record->uploadedBy,
                        $record->warehouseUploadedBy,
                        $record->user_id,
                    ))
                    ->tooltip(fn (FacilityServiceReadiness $record): ?string => UserDisplayName::uploadedByTooltip(
                        $record->uploadedBy,
                        $record->warehouseUploadedBy,
                        $record->user_id,
                    ))
                    ->visible(fn (): bool => UserDisplayName::canViewUploaders())
                    ->toggleable(),
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
                SelectFilter::make('units_id')
                    ->label(__('aho.fields.provision_unit'))
                    ->relationship('unit', 'code', modifyQueryUsing: fn (Builder $query): Builder => SelectOptions::orderByDisplayName($query, 'code'))
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)
                    ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::fromDisplayNameQuery(FacilityProvisionUnit::query(), $search, 'infra_id'))
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
            'index' => ListServiceReadiness::route('/'),
            'create' => CreateServiceReadiness::route('/create'),
            'edit' => EditServiceReadiness::route('/{record}/edit'),
        ];
    }
}
