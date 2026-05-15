<?php

namespace App\Filament\Resources\ResourceTypes;

use App\Filament\Clusters\Publications;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\ResourceTypes\Pages\CreateResourceType;
use App\Filament\Resources\ResourceTypes\Pages\EditResourceType;
use App\Filament\Resources\ResourceTypes\Pages\ListResourceTypes;
use App\Models\ResourceType;
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

class ResourceTypeResource extends Resource
{
    protected static ?string $model = ResourceType::class;

    protected static ?string $cluster = Publications::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'References';

    protected static ?string $slug = 'types';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.references');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.resource_types.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.resource_types.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.resource_types.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return WarehouseForm::configure($schema, static::getModel());
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('display_name')->label(__('aho.fields.type'))->wrap(),
            TextColumn::make('code')->label(__('aho.fields.code'))->searchable()->sortable(),
            TextColumn::make('products_count')->label(__('aho.fields.products_count'))->sortable(),
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
            'index' => ListResourceTypes::route('/'),
            'create' => CreateResourceType::route('/create'),
            'edit' => EditResourceType::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('translations')
            ->withCount('products');
    }
}
