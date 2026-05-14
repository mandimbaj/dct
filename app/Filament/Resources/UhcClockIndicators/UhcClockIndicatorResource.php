<?php

namespace App\Filament\Resources\UhcClockIndicators;

use App\Filament\Clusters\UhcClock;
use App\Filament\Resources\UhcClockIndicators\Pages\CreateUhcClockIndicator;
use App\Filament\Resources\UhcClockIndicators\Pages\EditUhcClockIndicator;
use App\Filament\Resources\UhcClockIndicators\Pages\ListUhcClockIndicators;
use App\Models\UhcClockIndicator;
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
use Filament\Tables\Table;
use UnitEnum;

class UhcClockIndicatorResource extends Resource
{
    protected static ?string $model = UhcClockIndicator::class;

    protected static ?string $cluster = UhcClock::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'References';

    protected static ?string $slug = 'indicators';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.references');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.uhc_clock_indicators.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.uhc_clock_indicators.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.uhc_clock_indicators.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return WarehouseForm::configure($schema, static::getModel());
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('indicator.afrocode')->label(__('aho.fields.code'))->searchable(),
            TextColumn::make('indicator.display_name')->label(__('aho.fields.indicator'))->wrap(),
            TextColumn::make('Indicator_type')->label(__('aho.fields.type'))->badge(),
            TextColumn::make('group.display_name')->label(__('aho.fields.group'))->toggleable(),
            TextColumn::make('run_id')->label(__('aho.fields.run'))->toggleable(),
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

    public static function getPages(): array
    {
        return [
            'index' => ListUhcClockIndicators::route('/'),
            'create' => CreateUhcClockIndicator::route('/create'),
            'edit' => EditUhcClockIndicator::route('/{record}/edit'),
        ];
    }
}
