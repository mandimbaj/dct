<?php

namespace App\Filament\Resources\ResourceCategories;

use App\Filament\Clusters\Publications;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\ResourceCategories\Pages\CreateResourceCategory;
use App\Filament\Resources\ResourceCategories\Pages\EditResourceCategory;
use App\Filament\Resources\ResourceCategories\Pages\ListResourceCategories;
use App\Models\ResourceCategory;
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

class ResourceCategoryResource extends Resource
{
    protected static ?string $model = ResourceCategory::class;

    protected static ?string $cluster = Publications::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static string|UnitEnum|null $navigationGroup = 'References';

    protected static ?string $slug = 'categories';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.references');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.resource_categories.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.resource_categories.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.resource_categories.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return WarehouseForm::configure($schema, static::getModel());
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('display_name')->label(__('aho.fields.category'))->wrap(),
            TextColumn::make('code')->label(__('aho.fields.code'))->searchable()->sortable(),
            TextColumn::make('type.display_name')->label(__('aho.fields.type'))->toggleable(),
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
            'index' => ListResourceCategories::route('/'),
            'create' => CreateResourceCategory::route('/create'),
            'edit' => EditResourceCategory::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['translations', 'type.translations'])
            ->withCount('products');
    }
}
