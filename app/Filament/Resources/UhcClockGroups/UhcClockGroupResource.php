<?php

namespace App\Filament\Resources\UhcClockGroups;

use App\Filament\Clusters\UhcClock;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\UhcClockGroups\Pages\CreateUhcClockGroup;
use App\Filament\Resources\UhcClockGroups\Pages\EditUhcClockGroup;
use App\Filament\Resources\UhcClockGroups\Pages\ListUhcClockGroups;
use App\Models\UhcClockIndicatorGroup;
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
use UnitEnum;

class UhcClockGroupResource extends Resource
{
    protected static ?string $model = UhcClockIndicatorGroup::class;

    protected static ?string $cluster = UhcClock::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static string|UnitEnum|null $navigationGroup = 'References';

    protected static ?string $slug = 'groups';

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.references');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.uhc_clock_groups.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.uhc_clock_groups.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.uhc_clock_groups.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return WarehouseForm::configure($schema, static::getModel());
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('display_name')->label(__('aho.fields.group'))->wrap(),
            TextColumn::make('code')->label(__('aho.fields.code'))->searchable()->sortable(),
            TextColumn::make('indicators_count')->label(__('aho.fields.indicators_count'))->counts('indicators')->sortable(),
            TextColumn::make('themes_count')->label(__('aho.fields.themes_count'))->counts('themes')->sortable(),
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
            'index' => ListUhcClockGroups::route('/'),
            'create' => CreateUhcClockGroup::route('/create'),
            'edit' => EditUhcClockGroup::route('/{record}/edit'),
        ];
    }
}
