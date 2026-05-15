<?php

namespace App\Filament\Resources\DataElementGroups;

use App\Filament\Clusters\DataElements;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\DataElementGroups\Pages\CreateDataElementGroup;
use App\Filament\Resources\DataElementGroups\Pages\EditDataElementGroup;
use App\Filament\Resources\DataElementGroups\Pages\ListDataElementGroups;
use App\Models\DataElementGroup;
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

class DataElementGroupResource extends Resource
{
    protected static ?string $model = DataElementGroup::class;

    protected static ?string $cluster = DataElements::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static string|UnitEnum|null $navigationGroup = 'References';

    protected static ?string $slug = 'groups';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.references');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.data_element_groups.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.data_element_groups.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.data_element_groups.plural');
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
            TextColumn::make('data_elements_count')->label(__('aho.fields.data_elements_count'))->counts('dataElements')->sortable(),
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
            'index' => ListDataElementGroups::route('/'),
            'create' => CreateDataElementGroup::route('/create'),
            'edit' => EditDataElementGroup::route('/{record}/edit'),
        ];
    }
}
