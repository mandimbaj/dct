<?php

namespace App\Filament\Resources\ImportRecords;

use App\Filament\Clusters\DataWizard;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\ImportRecords\Pages\CreateImportRecord;
use App\Filament\Resources\ImportRecords\Pages\EditImportRecord;
use App\Filament\Resources\ImportRecords\Pages\ListImportRecords;
use App\Models\ImportRecord;
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

class ImportRecordResource extends Resource
{
    protected static ?string $model = ImportRecord::class;

    protected static ?string $cluster = DataWizard::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static string|UnitEnum|null $navigationGroup = 'Data';

    protected static ?string $slug = 'imports';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.data');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.imports.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.imports.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.imports.plural');
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
                TextColumn::make('loader')->label(__('aho.fields.loader'))->searchable()->wrap(),
                TextColumn::make('serializer')->label(__('aho.fields.serializer'))->searchable()->toggleable(),
                TextColumn::make('record_count')->label(__('aho.fields.processed_rows'))->numeric()->sortable(),
                TextColumn::make('object_id')->label(__('aho.fields.object_id'))->numeric()->toggleable(),
                TextColumn::make('failed_rows_count')->label(__('aho.fields.failed_rows'))->counts('failedRows')->sortable(),
                TextColumn::make('user_id')->label(__('aho.fields.user'))->numeric()->toggleable(),
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
            'index' => ListImportRecords::route('/'),
            'create' => CreateImportRecord::route('/create'),
            'edit' => EditImportRecord::route('/{record}/edit'),
        ];
    }
}
