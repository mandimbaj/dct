<?php

namespace App\Filament\Resources\HealthFacilities;

use App\Filament\Clusters\Facilities;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\HealthFacilities\Pages\CreateHealthFacility;
use App\Filament\Resources\HealthFacilities\Pages\EditHealthFacility;
use App\Filament\Resources\HealthFacilities\Pages\ListHealthFacilities;
use App\Models\HealthFacility;
use App\Support\FilamentSearch;
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

class HealthFacilityResource extends Resource
{
    protected static ?string $model = HealthFacility::class;

    protected static ?string $cluster = Facilities::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|UnitEnum|null $navigationGroup = 'Data';

    protected static ?string $slug = 'facilities';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'display_name';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.data');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.health_facilities.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.health_facilities.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.health_facilities.plural');
    }

    /**
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return [
            'name',
            'shortname',
            'code',
            'status',
            'location.code',
            'location.translations.name',
            'type.code',
            'type.translations.name',
            'owner.code',
            'owner.translations.name',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return WarehouseForm::configure($schema, static::getModel());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('facility_id', 'desc')
            ->searchUsing(function (Builder $query, string $search): void {
                FilamentSearch::apply(
                    query: $query,
                    search: $search,
                    columns: ['name', 'shortname', 'code', 'status'],
                    relations: [
                        'location' => ['code', 'iso_alpha', 'iso_number'],
                        'location.translations' => ['name'],
                        'type' => ['code'],
                        'type.translations' => ['name'],
                        'owner' => ['code'],
                        'owner.translations' => ['name'],
                    ],
                    numericColumns: ['facility_id'],
                );
            })
            ->columns([
                TextColumn::make('facility_id')->label(__('aho.fields.id'))->sortable()->toggleable(),
                TextColumn::make('display_name')->label(__('aho.fields.facility'))->searchable(['name', 'shortname', 'code'])->wrap(),
                TextColumn::make('code')->label(__('aho.fields.code'))->searchable()->sortable(),
                TextColumn::make('type.display_name')->label(__('aho.fields.type'))->toggleable(),
                TextColumn::make('owner.display_name')->label(__('aho.fields.owner'))->toggleable(),
                TextColumn::make('location.display_name')->label(__('aho.fields.location'))->toggleable(),
                TextColumn::make('status')->label(__('aho.fields.status'))->badge()->toggleable(),
                TextColumn::make('date_created')->label(__('aho.fields.creation'))->dateTime()->sortable()->toggleable(),
                TextColumn::make('date_lastupdated')->label(__('aho.fields.modification'))->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type_id')
                    ->label(__('aho.fields.type'))
                    ->relationship('type', 'code')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)
                    ->searchable(),
                SelectFilter::make('owner_id')
                    ->label(__('aho.fields.owner'))
                    ->relationship('owner', 'code')
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

    public static function getEloquentQuery(): Builder
    {
        return UserCountryAccess::scope(parent::getEloquentQuery(), 'location_id');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHealthFacilities::route('/'),
            'create' => CreateHealthFacility::route('/create'),
            'edit' => EditHealthFacility::route('/{record}/edit'),
        ];
    }
}
