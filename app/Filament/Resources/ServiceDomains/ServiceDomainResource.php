<?php

namespace App\Filament\Resources\ServiceDomains;

use App\Filament\Clusters\Facilities;
use App\Filament\Resources\Concerns\SearchesTranslatedRecords;
use App\Filament\Resources\ServiceDomains\Pages\CreateServiceDomain;
use App\Filament\Resources\ServiceDomains\Pages\EditServiceDomain;
use App\Filament\Resources\ServiceDomains\Pages\ListServiceDomains;
use App\Models\FacilityServiceDomain;
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

class ServiceDomainResource extends Resource
{
    use SearchesTranslatedRecords;

    protected static ?string $model = FacilityServiceDomain::class;

    protected static ?string $cluster = Facilities::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolderOpen;

    protected static string|UnitEnum|null $navigationGroup = 'References';

    protected static ?string $slug = 'service-domains';

    protected static ?int $navigationSort = 8;

    protected static ?string $recordTitleAttribute = 'display_name';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.references');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.service_domains.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.service_domains.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.service_domains.plural');
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
                    columns: ['code', 'level'],
                    relations: [
                        'translations' => ['name', 'shortname'],
                        'parent' => ['code'],
                        'parent.translations' => ['name'],
                    ],
                    numericColumns: ['domain_id', 'category'],
                );
            })
            ->columns([
                TextColumn::make('display_name')->label(__('aho.fields.service_domain'))->wrap(),
                TextColumn::make('code')->label(__('aho.fields.code'))->searchable()->sortable(),
                TextColumn::make('category')
                    ->label(__('aho.fields.category'))
                    ->formatStateUsing(fn ($state): string => match ((int) $state) {
                        1 => __('aho.facility_service_categories.availability'),
                        2 => __('aho.facility_service_categories.capacity'),
                        3 => __('aho.facility_service_categories.readiness'),
                        default => (string) ($state ?? ''),
                    })
                    ->badge()
                    ->sortable(),
                TextColumn::make('level')->label(__('aho.fields.level'))->badge()->sortable(),
                TextColumn::make('parent.display_name')->label(__('aho.fields.parent'))->placeholder('-')->toggleable(),
                TextColumn::make('provision_units_count')->label(__('aho.fields.provision_units_count'))->counts('provisionUnits')->sortable(),
                TextColumn::make('interventions_count')->label(__('aho.fields.interventions_count'))->counts('interventions')->sortable(),
                TextColumn::make('date_created')->label(__('aho.fields.creation'))->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('date_lastupdated')->label(__('aho.fields.modification'))->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label(__('aho.fields.category'))
                    ->options([
                        1 => __('aho.facility_service_categories.availability'),
                        2 => __('aho.facility_service_categories.capacity'),
                        3 => __('aho.facility_service_categories.readiness'),
                    ]),
                SelectFilter::make('parent_id')
                    ->label(__('aho.fields.parent'))
                    ->relationship('parent', 'code')
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
            'index' => ListServiceDomains::route('/'),
            'create' => CreateServiceDomain::route('/create'),
            'edit' => EditServiceDomain::route('/{record}/edit'),
        ];
    }
}
