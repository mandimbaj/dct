<?php

namespace App\Filament\Resources\KnowledgeResourceTags;

use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use App\Support\WarehouseForm;
use App\Filament\Resources\KnowledgeResourceTags\Pages\EditKnowledgeResourceTag;
use App\Filament\Resources\KnowledgeResourceTags\Pages\CreateKnowledgeResourceTag;
use App\Filament\Clusters\Publications;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\Concerns\UsesFallbackResourcePermission;
use App\Filament\Resources\KnowledgeProducts\KnowledgeProductResource;
use App\Filament\Resources\KnowledgeResourceTags\Pages\ListKnowledgeResourceTags;
use App\Models\KnowledgeResourceTag;
use App\Support\FilamentSearch;
use App\Support\UserCountryAccess;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class KnowledgeResourceTagResource extends Resource
{
    use UsesFallbackResourcePermission;

    protected static ?string $model = KnowledgeResourceTag::class;

    protected static ?string $cluster = Publications::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static string|UnitEnum|null $navigationGroup = 'References';

    protected static ?string $slug = 'resource-tags';

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.references');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.knowledge_resource_tags.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.knowledge_resource_tags.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.knowledge_resource_tags.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return WarehouseForm::configure($schema, static::getModel());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('tag_id')
            ->searchUsing(function (Builder $query, string $search): void {
                FilamentSearch::apply(
                    query: $query,
                    search: $search,
                    relations: [
                        'publication.translations' => ['title', 'author'],
                        'location.translations' => ['name'],
                    ],
                    numericColumns: ['tag_id'],
                );
            })
            ->columns([
                TextColumn::make('tag_id')->label(__('aho.fields.id'))->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('publication.display_title')->label(__('aho.fields.title'))->wrap(),
                TextColumn::make('location.display_name')->label(__('aho.fields.location'))->wrap(),
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

    public static function getEloquentQuery(): Builder
    {
        return UserCountryAccess::scope(parent::getEloquentQuery()->with([
            'publication.translations',
            'location.translations',
        ]));
    }

    protected static function fallbackPermissionResources(): array
    {
        return [KnowledgeProductResource::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKnowledgeResourceTags::route('/'),
            'create' => CreateKnowledgeResourceTag::route('/create'),
            'edit' => EditKnowledgeResourceTag::route('/{record}/edit'),
        ];
    }
}
