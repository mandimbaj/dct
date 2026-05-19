<?php

namespace App\Filament\Resources\ProvisionUnits;

use App\Filament\Clusters\Facilities;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\Concerns\SearchesTranslatedRecords;
use App\Filament\Resources\ProvisionUnits\Pages\CreateProvisionUnit;
use App\Filament\Resources\ProvisionUnits\Pages\EditProvisionUnit;
use App\Filament\Resources\ProvisionUnits\Pages\ListProvisionUnits;
use App\Models\FacilityProvisionUnit;
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

class ProvisionUnitResource extends Resource
{
    use SearchesTranslatedRecords;

    protected static ?string $model = FacilityProvisionUnit::class;

    protected static ?string $cluster = Facilities::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static string|UnitEnum|null $navigationGroup = 'References';

    protected static ?string $slug = 'provision-units';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'display_name';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.references');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.provision_units.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.provision_units.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.provision_units.plural');
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
                    numericColumns: ['infra_id'],
                );
            })
            ->columns([
                TextColumn::make('display_name')->label(__('aho.fields.provision_unit'))->wrap(),
                TextColumn::make('code')->label(__('aho.fields.code'))->searchable()->sortable(),
                TextColumn::make('domain.display_name')->label(__('aho.fields.service_domain'))->wrap()->toggleable(),
                TextColumn::make('service_capacities_count')->label(__('aho.fields.capacity_count'))->counts('serviceCapacities')->sortable(),
                TextColumn::make('service_readiness_count')->label(__('aho.fields.readiness_count'))->counts('serviceReadiness')->sortable(),
                TextColumn::make('date_created')->label(__('aho.fields.creation'))->dateTime()->sortable()->toggleable(),
                TextColumn::make('date_lastupdated')->label(__('aho.fields.modification'))->dateTime()->sortable()->toggleable(),
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
            'index' => ListProvisionUnits::route('/'),
            'create' => CreateProvisionUnit::route('/create'),
            'edit' => EditProvisionUnit::route('/{record}/edit'),
        ];
    }
}
