<?php

namespace App\Filament\Resources\UhcPriorityIndicators;

use App\Filament\Clusters\UhcClock;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\UhcPriorityIndicators\Pages\CreateUhcPriorityIndicator;
use App\Filament\Resources\UhcPriorityIndicators\Pages\EditUhcPriorityIndicator;
use App\Filament\Resources\UhcPriorityIndicators\Pages\ListUhcPriorityIndicators;
use App\Models\PriorityIndicatorValue;
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
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class UhcPriorityIndicatorResource extends Resource
{
    protected static ?string $model = PriorityIndicatorValue::class;

    protected static ?string $cluster = UhcClock::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;

    protected static string|UnitEnum|null $navigationGroup = 'Data';

    protected static ?string $slug = 'priority-indicators';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.data');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.uhc_priority_indicators.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.uhc_priority_indicators.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.uhc_priority_indicators.plural');
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
                    numericColumns: ['fact_id', 'value_received'],
                );
            })
            ->columns([
                TextColumn::make('fact_id')->label(__('aho.fields.id'))->sortable()->toggleable(),
                TextColumn::make('indicator.afrocode')->label(__('aho.fields.code'))->searchable()->sortable(),
                TextColumn::make('indicator.display_name')->label(__('aho.fields.indicator'))->wrap(),
                TextColumn::make('location.display_name')->label(__('aho.fields.location'))->toggleable(),
                TextColumn::make('period')->label(__('aho.fields.period'))->toggleable(),
                TextColumn::make('value_received')->label(__('aho.fields.value_received'))->numeric()->sortable(),
                TextColumn::make('priority')->label(__('aho.fields.priority'))->badge()->sortable(),
                TextColumn::make('date_created')->label(__('aho.fields.creation'))->dateTime()->sortable()->toggleable(),
                TextColumn::make('date_lastupdated')->label(__('aho.fields.modification'))->dateTime()->sortable()->toggleable(),
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
            'index' => ListUhcPriorityIndicators::route('/'),
            'create' => CreateUhcPriorityIndicator::route('/create'),
            'edit' => EditUhcPriorityIndicator::route('/{record}/edit'),
        ];
    }
}
