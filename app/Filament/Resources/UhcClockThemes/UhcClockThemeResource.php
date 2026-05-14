<?php

namespace App\Filament\Resources\UhcClockThemes;

use App\Filament\Clusters\UhcClock;
use App\Filament\Resources\UhcClockThemes\Pages\CreateUhcClockTheme;
use App\Filament\Resources\UhcClockThemes\Pages\EditUhcClockTheme;
use App\Filament\Resources\UhcClockThemes\Pages\ListUhcClockThemes;
use App\Models\UhcClockTheme;
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

class UhcClockThemeResource extends Resource
{
    protected static ?string $model = UhcClockTheme::class;

    protected static ?string $cluster = UhcClock::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolderOpen;

    protected static string|UnitEnum|null $navigationGroup = 'References';

    protected static ?string $slug = 'themes';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.references');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.uhc_clock_themes.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.uhc_clock_themes.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.uhc_clock_themes.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return WarehouseForm::configure($schema, static::getModel());
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('display_name')->label(__('aho.fields.theme'))->wrap(),
            TextColumn::make('level')->label(__('aho.fields.level'))->badge()->sortable(),
            TextColumn::make('group.display_name')->label(__('aho.fields.group'))->toggleable(),
            TextColumn::make('parent.display_name')->label(__('aho.fields.parent'))->placeholder('-')->toggleable(),
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
            'index' => ListUhcClockThemes::route('/'),
            'create' => CreateUhcClockTheme::route('/create'),
            'edit' => EditUhcClockTheme::route('/{record}/edit'),
        ];
    }
}
