<?php

namespace App\Filament\Resources\FailedImportRows;

use App\Filament\Clusters\DataQuality;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\FailedImportRows\Pages\CreateFailedImportRow;
use App\Filament\Resources\FailedImportRows\Pages\EditFailedImportRow;
use App\Filament\Resources\FailedImportRows\Pages\ListFailedImportRows;
use App\Models\FailedImportRow;
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

class FailedImportRowResource extends Resource
{
    protected static ?string $model = FailedImportRow::class;

    protected static ?string $cluster = DataQuality::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?string $slug = 'failed-import-rows';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return null;
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.failed_import_rows.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.failed_import_rows.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.failed_import_rows.plural');
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
                TextColumn::make('run_id')->label(__('aho.fields.run'))->numeric()->sortable(),
                TextColumn::make('row')->label(__('aho.fields.row'))->numeric()->sortable(),
                TextColumn::make('success')->label(__('aho.fields.status'))->badge()->sortable(),
                TextColumn::make('fail_reason')->label(__('aho.fields.validation_error'))->wrap()->searchable(),
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
            'index' => ListFailedImportRows::route('/'),
            'create' => CreateFailedImportRow::route('/create'),
            'edit' => EditFailedImportRow::route('/{record}/edit'),
        ];
    }
}
