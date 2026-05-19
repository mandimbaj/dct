<?php

namespace App\Filament\Resources\HealthWorkforceValues;

use App\Filament\Clusters\HealthWorkforce;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\HealthWorkforceValues\Pages\CreateHealthWorkforceValue;
use App\Filament\Resources\HealthWorkforceValues\Pages\EditHealthWorkforceValue;
use App\Filament\Resources\HealthWorkforceValues\Pages\ListHealthWorkforceValues;
use App\Models\HealthWorkforceValue;
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

class HealthWorkforceValueResource extends Resource
{
    protected static ?string $model = HealthWorkforceValue::class;

    protected static ?string $cluster = HealthWorkforce::class;

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
        return __('aho.resources.health_workforce_values.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.health_workforce_values.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.health_workforce_values.plural');
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
                    columns: ['period', 'status'],
                    relations: [
                        'cadre' => ['code'],
                        'cadre.translations' => ['name'],
                        'location' => ['code', 'iso_alpha', 'iso_number'],
                        'location.translations' => ['name'],
                        'categoryOption' => ['code'],
                        'categoryOption.translations' => ['name'],
                        'dataSource' => ['code'],
                        'dataSource.translations' => ['name'],
                        'measureMethod' => ['code'],
                        'measureMethod.translations' => ['name'],
                    ],
                    numericColumns: ['fact_id', 'value'],
                );
            })
            ->columns([
                TextColumn::make('fact_id')->label(__('aho.fields.id'))->sortable()->toggleable(),
                TextColumn::make('cadre.display_name')->label(__('aho.fields.cadre'))->wrap(),
                TextColumn::make('location.display_name')->label(__('aho.fields.location'))->toggleable(),
                TextColumn::make('period')->label(__('aho.fields.period'))->sortable()->searchable(),
                TextColumn::make('categoryOption.display_name')->label(__('aho.fields.disaggregation'))->toggleable(),
                TextColumn::make('dataSource.display_name')->label(__('aho.fields.source'))->toggleable(),
                TextColumn::make('value')->label(__('aho.fields.value'))->numeric()->sortable(),
                TextColumn::make('status')->label(__('aho.fields.status'))->badge()->toggleable(),
                TextColumn::make('date_created')->label(__('aho.fields.creation'))->dateTime()->sortable()->toggleable(),
                TextColumn::make('date_lastupdated')->label(__('aho.fields.modification'))->dateTime()->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('cadre_id')
                    ->label(__('aho.fields.cadre'))
                    ->relationship('cadre', 'code')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)
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
                'cadre.translations',
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
            'index' => ListHealthWorkforceValues::route('/'),
            'create' => CreateHealthWorkforceValue::route('/create'),
            'edit' => EditHealthWorkforceValue::route('/{record}/edit'),
        ];
    }
}
