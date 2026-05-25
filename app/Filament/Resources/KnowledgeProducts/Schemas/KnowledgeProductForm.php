<?php

namespace App\Filament\Resources\KnowledgeProducts\Schemas;

use App\Filament\Resources\KnowledgeProducts\KnowledgeProductResource;
use App\Models\Country;
use App\Models\PublicationDomain;
use App\Models\ResourceCategory;
use App\Models\ResourceType;
use App\Support\ApprovalWorkflow;
use App\Support\SelectOptions;
use App\Support\UserCountryAccess;
use App\Support\UserPermissions;
use App\Support\WarehouseLocale;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class KnowledgeProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make(__('aho.knowledge_products.sections.identity'))
                    ->schema([
                        Select::make('translation_language_code')
                            ->label(__('aho.fields.language'))
                            ->options(fn (): array => WarehouseLocale::supported())
                            ->default(fn (): string => WarehouseLocale::current())
                            ->required(),

                        Hidden::make('code'),

                        TextInput::make('translation_title')
                            ->label(__('aho.fields.title'))
                            ->required()
                            ->maxLength(2000)
                            ->columnSpanFull(),

                        Select::make('location_id')
                            ->label(__('aho.fields.location'))
                            ->relationship('location', 'code', modifyQueryUsing: fn (Builder $query): Builder => UserCountryAccess::scopeLocations(
                                SelectOptions::orderByDisplayName($query->with('translations'), 'code'),
                            ))
                            ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)
                            ->options(fn (): array => SelectOptions::fromDisplayNameQuery(
                                UserCountryAccess::scopeLocations(Country::query()),
                                keyName: 'location_id',
                            ))
                            ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::fromDisplayNameQuery(
                                UserCountryAccess::scopeLocations(Country::query()),
                                $search,
                                'location_id',
                            ))
                            ->searchable()
                            ->required()
                            ->default(fn (): ?int => UserCountryAccess::canViewAllCountries() ? null : UserCountryAccess::locationId())
                            ->preload(),

                        Select::make('type_id')
                            ->label(__('aho.fields.type'))
                            ->relationship('type', 'code', modifyQueryUsing: fn (Builder $query): Builder => SelectOptions::orderByDisplayName($query, 'code'))
                            ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)
                            ->options(fn (): array => SelectOptions::fromDisplayNameQuery(ResourceType::query(), keyName: 'type_id'))
                            ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::fromDisplayNameQuery(ResourceType::query(), $search, 'type_id'))
                            ->live()
                            ->required()
                            ->afterStateUpdated(fn (Set $set): mixed => $set('categorization_id', null))
                            ->searchable()
                            ->preload(),

                        Select::make('categorization_id')
                            ->label(__('aho.fields.category'))
                            ->options(fn (Get $get): array => self::categoryOptions($get('type_id')))
                            ->getSearchResultsUsing(fn (Get $get, ?string $search): array => SelectOptions::filterAndSort(self::categoryOptions($get('type_id')), $search))
                            ->disabled(fn (Get $get): bool => blank($get('type_id')))
                            ->required()
                            ->searchable()
                            ->preload(),

                        Select::make('domains')
                            ->label(__('aho.fields.publication_domains'))
                            ->relationship('domains', 'code', modifyQueryUsing: fn (Builder $query): Builder => SelectOptions::orderByDisplayName($query, 'code'))
                            ->getOptionLabelFromRecordUsing(fn (PublicationDomain $record): string => $record->display_name)
                            ->options(fn (): array => SelectOptions::fromDisplayNameQuery(PublicationDomain::query(), keyName: 'domain_id'))
                            ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::fromDisplayNameQuery(PublicationDomain::query(), $search, 'domain_id'))
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make(__('aho.knowledge_products.sections.content'))
                    ->schema([
                        TextInput::make('translation_author')
                            ->label(__('aho.fields.author'))
                            ->required()
                            ->maxLength(200),

                        TextInput::make('translation_year_published')
                            ->label(__('aho.fields.year_published'))
                            ->required()
                            ->numeric()
                            ->minValue(1900)
                            ->maxValue((int) date('Y') + 1),

                        Textarea::make('translation_abstract')
                            ->label(__('aho.fields.abstract'))
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('translation_description')
                            ->label(__('aho.fields.description'))
                            ->rows(5)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make(__('aho.knowledge_products.sections.files'))
                    ->schema([
                        TextInput::make('translation_internal_url')
                            ->label(__('aho.fields.internal_url'))
                            ->maxLength(2000)
                            ->helperText(__('aho.knowledge_products.help.internal_url')),

                        TextInput::make('translation_external_url')
                            ->label(__('aho.fields.external_url'))
                            ->url()
                            ->maxLength(2083),

                        TextInput::make('translation_cover_image')
                            ->label(__('aho.fields.cover_image'))
                            ->maxLength(100)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make(__('aho.fields.approval_status'))
                    ->schema([
                        Select::make('comment')
                            ->label(__('aho.fields.approval_status'))
                            ->options(fn (string $operation): array => $operation === 'create'
                                ? self::pendingApprovalOption()
                                : ApprovalWorkflow::options())
                            ->default(ApprovalWorkflow::STATUS_PENDING)
                            ->required(fn (string $operation): bool => $operation === 'create' || self::canApprove())
                            ->disabled(fn (string $operation): bool => $operation === 'create' || ! self::canApprove())
                            ->dehydrated(fn (string $operation): bool => $operation === 'create' || self::canApprove()),
                    ])
                    ->columns(1)
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return array<int|string, string>
     */
    private static function categoryOptions(mixed $typeId): array
    {
        if (blank($typeId)) {
            return [];
        }

        return ResourceCategory::query()
            ->where('type_id', $typeId)
            ->with('translations')
            ->get()
            ->mapWithKeys(fn (ResourceCategory $category): array => [
                $category->getKey() => $category->display_name,
            ])
            ->sortBy(fn (string $label): string => mb_strtolower($label))
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private static function pendingApprovalOption(): array
    {
        return [
            ApprovalWorkflow::STATUS_PENDING => ApprovalWorkflow::label(ApprovalWorkflow::STATUS_PENDING),
        ];
    }

    private static function canApprove(): bool
    {
        return (bool) (
            auth()->user()
            && UserPermissions::allowsResource(auth()->user(), KnowledgeProductResource::class, UserPermissions::ACTION_APPROVE)
        );
    }
}
