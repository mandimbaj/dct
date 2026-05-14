<?php

namespace App\Filament\Resources\PublicationDomains;

use App\Filament\Clusters\Publications;
use App\Filament\Resources\PublicationDomains\Pages\CreatePublicationDomain;
use App\Filament\Resources\PublicationDomains\Pages\EditPublicationDomain;
use App\Filament\Resources\PublicationDomains\Pages\ListPublicationDomains;
use App\Models\PublicationDomain;
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

class PublicationDomainResource extends Resource
{
    protected static ?string $model = PublicationDomain::class;

    protected static ?string $cluster = Publications::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolderOpen;

    protected static string|UnitEnum|null $navigationGroup = 'References';

    protected static ?string $slug = 'domains';

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.references');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.publication_domains.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.publication_domains.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.publication_domains.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return WarehouseForm::configure($schema, static::getModel());
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('display_name')->label(__('aho.fields.domain'))->wrap(),
            TextColumn::make('code')->label(__('aho.fields.code'))->searchable()->sortable(),
            TextColumn::make('parent.display_name')->label(__('aho.fields.parent'))->placeholder('-')->toggleable(),
            TextColumn::make('products_count')->label(__('aho.fields.products_count'))->counts('products')->sortable(),
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
            'index' => ListPublicationDomains::route('/'),
            'create' => CreatePublicationDomain::route('/create'),
            'edit' => EditPublicationDomain::route('/{record}/edit'),
        ];
    }
}
