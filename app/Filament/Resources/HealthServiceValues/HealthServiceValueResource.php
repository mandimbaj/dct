<?php

namespace App\Filament\Resources\HealthServiceValues;

use App\Filament\Clusters\HealthServices;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\HealthServiceValues\Pages\CreateHealthServiceValue;
use App\Filament\Resources\HealthServiceValues\Pages\EditHealthServiceValue;
use App\Filament\Resources\HealthServiceValues\Pages\ListHealthServiceValues;
use App\Models\HealthServiceValue;
use App\Support\CountryTableFilter;
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

class HealthServiceValueResource extends Resource
{
    protected static ?string $model = HealthServiceValue::class;

    protected static ?string $cluster = HealthServices::class;

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
        return __('aho.resources.health_service_values.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.health_service_values.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.health_service_values.plural');
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
                        'indicator' => ['afrocode', 'gen_code'],
                        'indicator.translations' => ['name', 'shortname', 'definition'],
                        'location' => ['code', 'iso_alpha', 'iso_number'],
                        'location.translations' => ['name'],
                        'categoryOption' => ['code'],
                        'categoryOption.translations' => ['name'],
                        'dataSource' => ['code'],
                        'dataSource.translations' => ['name'],
                        'measureMethod' => ['code'],
                        'measureMethod.translations' => ['name'],
                    ],
                    numericColumns: ['fact_id', 'value_received', 'value_calculated'],
                );
            })
            ->columns([
                TextColumn::make('fact_id')->label(__('aho.fields.id'))->sortable()->toggleable(),
                TextColumn::make('indicator.afrocode')->label(__('aho.fields.code'))->searchable()->sortable(),
                TextColumn::make('indicator.display_name')->label(__('aho.fields.indicator'))->wrap()->toggleable(),
                TextColumn::make('location.display_name')->label(__('aho.fields.location'))->toggleable(),
                TextColumn::make('period')->label(__('aho.fields.period'))->sortable()->searchable(),
                TextColumn::make('value_received')->label(__('aho.fields.value_received'))->numeric()->sortable(),
                TextColumn::make('value_calculated')->label(__('aho.fields.value_calculated'))->numeric()->sortable()->toggleable(),
                TextColumn::make('dataSource.display_name')->label(__('aho.fields.source'))->toggleable(),
                TextColumn::make('date_created')->label(__('aho.fields.creation'))->dateTime()->sortable()->toggleable(),
                TextColumn::make('date_lastupdated')->label(__('aho.fields.modification'))->dateTime()->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('indicator_id')
                    ->label(__('aho.fields.indicator'))
                    ->relationship('indicator', 'afrocode')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => trim(($record->afrocode ? "{$record->afrocode} - " : '').$record->display_name))
                    ->searchable(),
                CountryTableFilter::make(),
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
        return UserCountryAccess::scope(
            parent::getEloquentQuery()->with([
                'indicator.translations',
                'location.translations',
                'categoryOption.translations',
                'dataSource.translations',
                'measureMethod.translations',
            ]),
        );
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHealthServiceValues::route('/'),
            'create' => CreateHealthServiceValue::route('/create'),
            'edit' => EditHealthServiceValue::route('/{record}/edit'),
        ];
    }
}
