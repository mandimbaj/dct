<?php

namespace App\Filament\Resources\ServiceCapacities;

use App\Filament\Clusters\Facilities;
use App\Filament\Resources\Concerns\ScopesFacilityCountryAccess;
use App\Filament\Resources\ServiceCapacities\Pages\CreateServiceCapacity;
use App\Filament\Resources\ServiceCapacities\Pages\EditServiceCapacity;
use App\Filament\Resources\ServiceCapacities\Pages\ListServiceCapacities;
use App\Models\FacilityServiceCapacity;
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

class ServiceCapacityResource extends Resource
{
    use ScopesFacilityCountryAccess;

    protected static ?string $model = FacilityServiceCapacity::class;

    protected static ?string $cluster = Facilities::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Data';

    protected static ?string $slug = 'service-capacity';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'display_name';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.data');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.service_capacity.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.service_capacity.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.service_capacity.plural');
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
        return $table
            ->defaultSort('capacity_id', 'desc')
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
                    numericColumns: ['capacity_id', 'available', 'functional'],
                );
            })
            ->columns([
                TextColumn::make('capacity_id')->label(__('aho.fields.id'))->sortable()->toggleable(),
                TextColumn::make('facility.display_name')->label(__('aho.fields.facility'))->wrap()->toggleable(),
                TextColumn::make('domain.display_name')->label(__('aho.fields.service_domain'))->wrap()->toggleable(),
                TextColumn::make('unit.display_name')->label(__('aho.fields.provision_unit'))->wrap()->toggleable(),
                TextColumn::make('available')->label(__('aho.fields.available'))->numeric()->sortable(),
                TextColumn::make('functional')->label(__('aho.fields.functional'))->numeric()->sortable(),
                TextColumn::make('date_assessed')->label(__('aho.fields.date_assessed'))->date()->sortable(),
                TextColumn::make('date_created')->label(__('aho.fields.creation'))->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('date_lastupdated')->label(__('aho.fields.modification'))->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('facility_id')
                    ->label(__('aho.fields.facility'))
                    ->relationship('facility', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => trim(($record->code ? "{$record->code} - " : '').$record->display_name))
                    ->searchable(),
                SelectFilter::make('domain_id')
                    ->label(__('aho.fields.service_domain'))
                    ->relationship('domain', 'code')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)
                    ->searchable(),
                SelectFilter::make('units_id')
                    ->label(__('aho.fields.provision_unit'))
                    ->relationship('unit', 'code')
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
            'index' => ListServiceCapacities::route('/'),
            'create' => CreateServiceCapacity::route('/create'),
            'edit' => EditServiceCapacity::route('/{record}/edit'),
        ];
    }
}
