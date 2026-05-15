<?php

namespace App\Filament\Resources\DataElements;

use App\Filament\Clusters\DataElements as DataElementsCluster;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\DataElements\Pages\CreateDataElement;
use App\Filament\Resources\DataElements\Pages\EditDataElement;
use App\Filament\Resources\DataElements\Pages\ListDataElements;
use App\Models\DataElement;
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

class DataElementResource extends Resource
{
    protected static ?string $model = DataElement::class;

    protected static ?string $cluster = DataElementsCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static string|UnitEnum|null $navigationGroup = 'References';

    protected static ?string $slug = 'definitions';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.references');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.data_elements.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.data_elements.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.data_elements.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return WarehouseForm::configure($schema, static::getModel());
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('display_name')->label(__('aho.fields.data_element'))->wrap(),
            TextColumn::make('code')->label(__('aho.fields.code'))->searchable()->sortable(),
            TextColumn::make('aggregation_type')->label(__('aho.fields.aggregation_type'))->badge()->toggleable(),
            TextColumn::make('values_count')->label(__('aho.fields.values_count'))->counts('values')->sortable(),
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
            'index' => ListDataElements::route('/'),
            'create' => CreateDataElement::route('/create'),
            'edit' => EditDataElement::route('/{record}/edit'),
        ];
    }
}
