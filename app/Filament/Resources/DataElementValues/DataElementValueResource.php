<?php

namespace App\Filament\Resources\DataElementValues;

use App\Filament\Clusters\DataElements;
use App\Filament\Resources\DataElementValues\Pages\CreateDataElementValue;
use App\Filament\Resources\DataElementValues\Pages\EditDataElementValue;
use App\Filament\Resources\DataElementValues\Pages\ListDataElementValues;
use App\Models\DataElementValue;
use App\Support\FilamentSearch;
use App\Support\UserCountryAccess;
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

class DataElementValueResource extends Resource
{
    protected static ?string $model = DataElementValue::class;

    protected static ?string $cluster = DataElements::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;

    protected static string|UnitEnum|null $navigationGroup = 'Data';

    protected static ?string $slug = 'values';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.data');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.data_element_values.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.data_element_values.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.data_element_values.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return WarehouseForm::configure($schema, static::getModel());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('fact_id', 'desc')
            ->searchUsing(function (Builder $query, string $search): void {
                FilamentSearch::apply(
                    query: $query,
                    search: $search,
                    columns: ['period'],
                    relations: [
                        'dataElement' => ['code'],
                        'dataElement.translations' => ['name'],
                        'location' => ['code', 'iso_alpha', 'iso_number'],
                        'location.translations' => ['name'],
                        'categoryOption' => ['code'],
                        'categoryOption.translations' => ['name'],
                        'dataSource' => ['code'],
                        'dataSource.translations' => ['name'],
                    ],
                    numericColumns: ['fact_id', 'value', 'target_value'],
                );
            })
            ->columns([
                TextColumn::make('fact_id')->label(__('aho.fields.id'))->sortable()->toggleable(),
                TextColumn::make('dataElement.display_name')->label(__('aho.fields.data_element'))->wrap(),
                TextColumn::make('location.display_name')->label(__('aho.fields.location'))->toggleable(),
                TextColumn::make('period')->label(__('aho.fields.period'))->searchable()->sortable(),
                TextColumn::make('categoryOption.display_name')->label(__('aho.fields.disaggregation'))->toggleable(),
                TextColumn::make('dataSource.display_name')->label(__('aho.fields.source'))->toggleable(),
                TextColumn::make('value')->label(__('aho.fields.value'))->numeric()->sortable(),
                TextColumn::make('target_value')->label(__('aho.fields.target'))->numeric()->sortable()->toggleable(),
                TextColumn::make('date_created')->label(__('aho.fields.creation'))->dateTime()->sortable()->toggleable(),
                TextColumn::make('date_lastupdated')->label(__('aho.fields.modification'))->dateTime()->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('dataelement_id')
                    ->label(__('aho.fields.data_element'))
                    ->relationship('dataElement', 'code')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => trim(($record->code ? "{$record->code} - " : '').$record->display_name))
                    ->searchable(),
                SelectFilter::make('location_id')
                    ->label(__('aho.fields.location'))
                    ->relationship('location', 'code')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => trim(($record->code ? "{$record->code} - " : '').$record->display_name))
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
        return UserCountryAccess::scope(parent::getEloquentQuery());
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDataElementValues::route('/'),
            'create' => CreateDataElementValue::route('/create'),
            'edit' => EditDataElementValue::route('/{record}/edit'),
        ];
    }
}
