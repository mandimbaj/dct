<?php

namespace App\Filament\Resources\KnowledgeProducts;

use App\Filament\Clusters\Publications;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\KnowledgeProducts\Pages\CreateKnowledgeProduct;
use App\Filament\Resources\KnowledgeProducts\Pages\EditKnowledgeProduct;
use App\Filament\Resources\KnowledgeProducts\Pages\ListKnowledgeProducts;
use App\Filament\Resources\KnowledgeProducts\Schemas\KnowledgeProductForm;
use App\Models\KnowledgeProduct;
use App\Support\ApprovalWorkflow;
use App\Support\FilamentSearch;
use App\Support\UserCountryAccess;
use App\Support\UserPermissions;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class KnowledgeProductResource extends Resource
{
    protected static ?string $model = KnowledgeProduct::class;

    protected static ?string $cluster = Publications::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static string|UnitEnum|null $navigationGroup = 'Data';

    protected static ?string $slug = 'products';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.data');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.knowledge_products.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.knowledge_products.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.knowledge_products.plural');
    }

    /**
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return [
            'code',
            'comment',
            'translations.title',
            'translations.author',
            'translations.internal_url',
            'translations.external_url',
            'location.code',
            'location.translations.name',
            'type.code',
            'type.translations.name',
            'category.code',
            'category.translations.name',
        ];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->display_title;
    }

    /**
     * @return array<string, string>
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return array_filter([
            __('aho.fields.code') => $record->code,
            __('aho.fields.author') => $record->display_author,
            __('aho.fields.year') => (string) $record->display_year,
            __('aho.fields.location') => $record->location?->display_name,
        ]);
    }

    public static function form(Schema $schema): Schema
    {
        return KnowledgeProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('product_id', 'desc')
            ->searchUsing(function (Builder $query, string $search): void {
                FilamentSearch::apply(
                    query: $query,
                    search: $search,
                    columns: ['code', 'comment'],
                    relations: [
                        'translations' => ['title', 'author', 'internal_url', 'external_url'],
                        'location' => ['code', 'iso_alpha', 'iso_number'],
                        'location.translations' => ['name'],
                        'type' => ['code'],
                        'type.translations' => ['name'],
                        'category' => ['code'],
                        'category.translations' => ['name'],
                    ],
                    numericColumns: ['product_id'],
                    numericRelations: [
                        'translations' => ['year_published'],
                    ],
                );
            })
            ->columns([
                TextColumn::make('display_title')->label(__('aho.fields.title'))->searchable(['translations.title'])->wrap(),
                TextColumn::make('code')->label(__('aho.fields.code'))->searchable()->sortable(),
                TextColumn::make('display_author')->label(__('aho.fields.author'))->toggleable(),
                TextColumn::make('display_year')->label(__('aho.fields.year'))->sortable()->toggleable(),
                TextColumn::make('location.display_name')->label(__('aho.fields.location'))->toggleable(),
                TextColumn::make('type.display_name')->label(__('aho.fields.type'))->toggleable(),
                TextColumn::make('category.display_name')->label(__('aho.fields.category'))->toggleable(),
                TextColumn::make('publication_file_label')
                    ->label(__('aho.fields.file'))
                    ->badge()
                    ->placeholder(__('aho.fields.no_file'))
                    ->url(fn (KnowledgeProduct $record): ?string => $record->publication_file_url)
                    ->openUrlInNewTab()
                    ->toggleable(),
                TextColumn::make('comment')
                    ->label(__('aho.fields.approval_status'))
                    ->badge()
                    ->color(fn (?string $state): string => ApprovalWorkflow::color($state))
                    ->formatStateUsing(fn (?string $state): string => ApprovalWorkflow::label($state))
                    ->sortable(),
                TextColumn::make('approved_at')
                    ->label(__('aho.fields.approved_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('date_created')
                    ->label(__('aho.fields.creation'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('date_lastupdated')
                    ->label(__('aho.fields.modification'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('type_id')
                    ->label(__('aho.fields.type'))
                    ->relationship('type', 'code')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)
                    ->searchable(),
                SelectFilter::make('categorization_id')
                    ->label(__('aho.fields.category'))
                    ->relationship('category', 'code')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)
                    ->searchable(),
                SelectFilter::make('comment')
                    ->label(__('aho.fields.approval_status'))
                    ->options(fn (): array => ApprovalWorkflow::options()),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label(__('aho.actions.approve'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (KnowledgeProduct $record): bool => ! ApprovalWorkflow::isApproved($record)
                        && (bool) auth()->user()
                        && UserPermissions::allowsResource(auth()->user(), static::class, UserPermissions::ACTION_APPROVE))
                    ->action(function (KnowledgeProduct $record): void {
                        ApprovalWorkflow::approve($record);
                    }),
                EditAction::make(),
                DeleteAction::make(),
                Action::make('openPublication')
                    ->label(__('aho.actions.open_file'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (KnowledgeProduct $record): ?string => $record->publication_file_url)
                    ->openUrlInNewTab()
                    ->visible(fn (KnowledgeProduct $record): bool => filled($record->publication_file_url)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return UserCountryAccess::scope(
            parent::getEloquentQuery()->with([
                'translations',
                'location.translations',
                'type.translations',
                'category.translations',
            ]),
        );
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKnowledgeProducts::route('/'),
            'create' => CreateKnowledgeProduct::route('/create'),
            'edit' => EditKnowledgeProduct::route('/{record}/edit'),
        ];
    }
}
