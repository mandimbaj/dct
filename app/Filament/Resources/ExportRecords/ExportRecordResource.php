<?php

namespace App\Filament\Resources\ExportRecords;

use App\Filament\Clusters\Indicators;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\ExportRecords\Pages\CreateExportRecord;
use App\Filament\Resources\ExportRecords\Pages\EditExportRecord;
use App\Filament\Resources\ExportRecords\Pages\ListExportRecords;
use App\Models\ExportRecord;
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

class ExportRecordResource extends Resource
{
    protected static ?string $model = ExportRecord::class;

    protected static ?string $cluster = Indicators::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static string|UnitEnum|null $navigationGroup = 'Data wizard';

    protected static ?string $slug = 'exports';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.menus.data_wizard');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.exports.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.exports.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.exports.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return WarehouseForm::configure($schema, static::getModel());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')->label(__('aho.fields.id'))->sortable(),
                TextColumn::make('name')->label(__('aho.fields.name'))->searchable()->wrap(),
                TextColumn::make('file')->label(__('aho.fields.file'))->searchable()->wrap(),
                TextColumn::make('date')->label(__('aho.fields.completed'))->dateTime()->sortable(),
                TextColumn::make('created_at')->label(__('aho.fields.creation'))->dateTime()->sortable()->toggleable(),
                TextColumn::make('updated_at')->label(__('aho.fields.modification'))->dateTime()->sortable()->toggleable(),
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
            'index' => ListExportRecords::route('/'),
            'create' => CreateExportRecord::route('/create'),
            'edit' => EditExportRecord::route('/{record}/edit'),
        ];
    }
}
