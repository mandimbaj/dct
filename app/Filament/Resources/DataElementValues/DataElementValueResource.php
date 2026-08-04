<?php

namespace App\Filament\Resources\DataElementValues;

use App\Filament\Clusters\DataElements;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\DataElementValues\Pages\CreateDataElementValue;
use App\Filament\Resources\DataElementValues\Pages\EditDataElementValue;
use App\Filament\Resources\DataElementValues\Pages\ListDataElementValues;
use App\Models\Country;
use App\Models\DataElement;
use App\Models\DataElementValue;
use App\Models\DataSource;
use App\Models\IndicatorCategory;
use App\Models\ValueDataType;
use App\Support\ApprovalWorkflow;
use App\Support\CountryTableFilter;
use App\Support\FilamentSearch;
use App\Support\SelectOptions;
use App\Support\UserCountryAccess;
use App\Support\UserDisplayName;
use App\Support\UserPermissions;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use UnitEnum;

/**
 * Data-element fact-value resource.
 *
 * Data elements are lower-level inputs than indicators, so this module keeps their values and
 * definitions separate from the Indicators cluster.
 */
class DataElementValueResource extends Resource
{
    protected static ?string $model = DataElementValue::class;

    protected static ?string $cluster = DataElements::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;

    protected static string|UnitEnum|null $navigationGroup = 'Data';

    protected static ?string $slug = 'values';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.data');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.data_element_values.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.data_element_values.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.data_element_values.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Hidden::make('uuid'),

                Section::make(__('aho.form_sections.data_element_details'))
                    ->schema([
                        Select::make('dataelement_id')
                            ->label(__('aho.fields.data_element'))
                            ->relationship('dataElement', 'code', modifyQueryUsing: fn (Builder $query): Builder => SelectOptions::orderByDisplayName($query->with('translations'), 'code'))
                            ->getOptionLabelFromRecordUsing(fn (DataElement $record): string => $record->display_name)
                            ->options(fn (): array => SelectOptions::fromDisplayNameQuery(DataElement::query(), keyName: 'dataelement_id'))
                            ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::fromDisplayNameQuery(DataElement::query(), $search, 'dataelement_id'))
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('location_id')
                            ->label(__('aho.fields.location'))
                            ->relationship('location', 'code', modifyQueryUsing: fn (Builder $query): Builder => UserCountryAccess::scopeLocations(
                                SelectOptions::orderByDisplayName($query->with('translations'), 'code'),
                            ))
                            ->getOptionLabelFromRecordUsing(fn (Country $record): string => $record->display_name)
                            ->options(fn (): array => SelectOptions::fromDisplayNameQuery(
                                UserCountryAccess::scopeLocations(Country::query()),
                                keyName: 'location_id',
                            ))
                            ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::fromDisplayNameQuery(
                                UserCountryAccess::scopeLocations(Country::query()),
                                $search,
                                'location_id',
                            ))
                            ->default(fn (): ?int => UserCountryAccess::canViewAllCountries() ? null : UserCountryAccess::locationId())
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('categoryoption_id')
                            ->label(__('aho.fields.category_option'))
                            ->relationship('categoryOption', 'code', modifyQueryUsing: fn (Builder $query): Builder => SelectOptions::orderByDisplayName($query->with(['translations', 'parentCategory.translations']), 'code'))
                            ->getOptionLabelFromRecordUsing(fn (IndicatorCategory $record): string => self::categoryOptionLabel($record))
                            ->options(fn (): array => self::categoryOptionOptions())
                            ->getSearchResultsUsing(fn (?string $search): array => self::categoryOptionSearchResults($search))
                            ->default(999)
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('datasource_id')
                            ->label(__('aho.fields.source'))
                            ->relationship('dataSource', 'code', modifyQueryUsing: fn (Builder $query): Builder => SelectOptions::orderByDisplayName($query->with('translations'), 'code'))
                            ->getOptionLabelFromRecordUsing(fn (DataSource $record): string => $record->display_name)
                            ->options(fn (): array => SelectOptions::fromDisplayNameQuery(DataSource::query(), keyName: 'datasource_id'))
                            ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::fromDisplayNameQuery(DataSource::query(), $search, 'datasource_id'))
                            ->default(4)
                            ->searchable()
                            ->preload()
                            ->required(),

                        TextInput::make('start_year')
                            ->label(__('aho.fields.start'))
                            ->numeric()
                            ->minValue(1900)
                            ->maxValue((int) date('Y'))
                            ->default((int) date('Y'))
                            ->required(),

                        TextInput::make('end_year')
                            ->label(__('aho.fields.end'))
                            ->numeric()
                            ->minValue(1900)
                            ->maxValue((int) date('Y'))
                            ->default((int) date('Y'))
                            ->rules(['gte:start_year'])
                            ->required(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make(__('aho.form_sections.reporting_period_values'))
                    ->schema([
                        Select::make('valuetype_id')
                            ->label(__('aho.fields.value_type'))
                            ->relationship('valueType', 'code', modifyQueryUsing: fn (Builder $query): Builder => SelectOptions::orderByDisplayName($query->with('translations'), 'code'))
                            ->getOptionLabelFromRecordUsing(fn (ValueDataType $record): string => $record->display_name)
                            ->options(fn (): array => SelectOptions::fromDisplayNameQuery(ValueDataType::query(), keyName: 'valuetype_id'))
                            ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::fromDisplayNameQuery(ValueDataType::query(), $search, 'valuetype_id'))
                            ->default(1)
                            ->searchable()
                            ->preload()
                            ->required(),

                        TextInput::make('value')
                            ->label(__('aho.fields.value'))
                            ->numeric()
                            ->required(),

                        TextInput::make('target_value')
                            ->label(__('aho.fields.target'))
                            ->numeric(),

                        Select::make('comment')
                            ->label(__('aho.fields.approval_status'))
                            ->options(fn (string $operation): array => $operation === 'create'
                                ? self::pendingApprovalOption()
                                : ApprovalWorkflow::options())
                            ->default(ApprovalWorkflow::STATUS_PENDING)
                            ->disabled(fn (string $operation): bool => $operation === 'create' || ! self::canApprove())
                            ->dehydrated(fn (string $operation): bool => $operation === 'create' || self::canApprove())
                            ->required(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('fact_id', 'desc')
            ->searchUsing(function (Builder $query, string $search): void {
                FilamentSearch::apply(
                    query: $query,
                    search: $search,
                    columns: ['period'],
                    relations: [
                        'dataElement' => ['code'],
                        'dataElement.translations' => ['name'],
                        'location' => ['code', 'iso_alpha', 'iso_number'],
                        'location.translations' => ['name'],
                        'categoryOption' => ['code'],
                        'categoryOption.translations' => ['name'],
                        'dataSource' => ['code'],
                        'dataSource.translations' => ['name'],
                    ],
                    numericColumns: ['fact_id', 'value', 'target_value'],
                );
            })
            ->columns([
                TextColumn::make('fact_id')->label(__('aho.fields.id'))->sortable()->toggleable(),
                TextColumn::make('dataElement.display_name')->label(__('aho.fields.data_element'))->wrap(),
                TextColumn::make('location.display_name')->label(__('aho.fields.location'))->toggleable(),
                TextColumn::make('period')->label(__('aho.fields.period'))->searchable()->sortable(),
                TextColumn::make('categoryOption.display_name')->label(__('aho.fields.disaggregation'))->toggleable(),
                TextColumn::make('dataSource.display_name')->label(__('aho.fields.source'))->toggleable(),
                TextColumn::make('valueType.display_name')->label(__('aho.fields.value_type'))->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('value')->label(__('aho.fields.value'))->numeric()->sortable(),
                TextColumn::make('target_value')->label(__('aho.fields.target'))->numeric()->sortable()->toggleable(),
                TextColumn::make('comment')
                    ->label(__('aho.fields.approval_status'))
                    ->badge()
                    ->color(fn (?string $state): string => ApprovalWorkflow::color($state))
                    ->formatStateUsing(fn (?string $state): string => ApprovalWorkflow::label($state))
                    ->sortable(),
                TextColumn::make('date_created')->label(__('aho.fields.creation'))->dateTime()->sortable()->toggleable(),
                TextColumn::make('date_lastupdated')->label(__('aho.fields.modification'))->dateTime()->sortable()->toggleable(),
                TextColumn::make('uploadedBy.name')
                    ->label(__('aho.fields.uploaded_by'))
                    ->state(fn (DataElementValue $record): string => UserDisplayName::uploadedBy(
                        $record->uploadedBy,
                        $record->warehouseUploadedBy,
                        $record->user_id,
                    ))
                    ->tooltip(fn (DataElementValue $record): ?string => UserDisplayName::uploadedByTooltip(
                        $record->uploadedBy,
                        $record->warehouseUploadedBy,
                        $record->user_id,
                    ))
                    ->visible(fn (): bool => UserDisplayName::canViewUploaders())
                    ->wrap()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('dataelement_id')
                    ->label(__('aho.fields.data_element'))
                    ->relationship('dataElement', 'code', modifyQueryUsing: fn (Builder $query): Builder => SelectOptions::orderByDisplayName($query, 'code'))
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)
                    ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::fromDisplayNameQuery(DataElement::query(), $search, 'dataelement_id'))
                    ->searchable(),
                CountryTableFilter::make(),
                SelectFilter::make('comment')
                    ->label(__('aho.fields.approval_status'))
                    ->options(fn (): array => ApprovalWorkflow::options())
                    ->native(false),
            ])
            ->recordActions([
                Action::make('markApproved')
                    ->label(__('aho.actions.approve'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (DataElementValue $record): bool => $record->comment !== ApprovalWorkflow::STATUS_APPROVED && self::canApprove())
                    ->action(fn (DataElementValue $record): bool => $record->forceFill(['comment' => ApprovalWorkflow::STATUS_APPROVED])->save()),
                Action::make('markRejected')
                    ->label(__('aho.actions.reject'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (DataElementValue $record): bool => $record->comment !== ApprovalWorkflow::STATUS_REJECTED && self::canApprove())
                    ->action(fn (DataElementValue $record): bool => $record->forceFill(['comment' => ApprovalWorkflow::STATUS_REJECTED])->save()),
                Action::make('markPending')
                    ->label(__('aho.actions.pending'))
                    ->icon('heroicon-o-clock')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (DataElementValue $record): bool => $record->comment !== ApprovalWorkflow::STATUS_PENDING && self::canApprove())
                    ->action(fn (DataElementValue $record): bool => $record->forceFill(['comment' => ApprovalWorkflow::STATUS_PENDING])->save()),
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
        return UserCountryAccess::scope(
            parent::getEloquentQuery()->with([
                'dataElement.translations',
                'location.translations',
                'categoryOption.translations',
                'dataSource.translations',
                'valueType.translations',
                'uploadedBy',
                'warehouseUploadedBy',
            ]),
        );
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
            && UserPermissions::allowsResource(auth()->user(), static::class, UserPermissions::ACTION_APPROVE)
        );
    }

    /**
     * @return array<int|string, string|array<int, string>>
     */
    private static function categoryOptionOptions(): array
    {
        return self::categoryOptionRecords()
            ->groupBy(fn (IndicatorCategory $record): string => $record->parentCategory?->display_name ?: __('aho.data_integration.other'))
            ->map(fn ($group): array => $group
                ->mapWithKeys(fn (IndicatorCategory $record): array => [
                    $record->categoryoption_id => $record->display_name,
                ])
                ->all())
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private static function categoryOptionSearchResults(?string $search = null): array
    {
        $normalizedSearch = self::normalizeCategoryOption((string) $search);

        return self::categoryOptionRecords()
            ->filter(fn (IndicatorCategory $record): bool => blank($search) || str_contains(
                self::normalizeCategoryOption(self::categoryOptionLabel($record)),
                $normalizedSearch,
            ))
            ->mapWithKeys(fn (IndicatorCategory $record): array => [
                $record->categoryoption_id => self::categoryOptionLabel($record),
            ])
            ->all();
    }

    private static function categoryOptionRecords()
    {
        return IndicatorCategory::query()
            ->with(['translations', 'parentCategory.translations'])
            ->limit(SelectOptions::LIMIT)
            ->get()
            ->sortBy(
                fn (IndicatorCategory $record): string => self::normalizeCategoryOption(
                    ($record->parentCategory?->display_name ?? '').' '.$record->display_name,
                ),
                SORT_NATURAL,
            );
    }

    private static function categoryOptionLabel(IndicatorCategory $record): string
    {
        $group = $record->parentCategory?->display_name;
        $option = $record->display_name;

        return filled($group) ? "{$group} - {$option}" : $option;
    }

    private static function normalizeCategoryOption(string $value): string
    {
        return (string) Str::of($value)->ascii()->lower()->squish();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDataElementValues::route('/'),
            'create' => CreateDataElementValue::route('/create'),
            'edit' => EditDataElementValue::route('/{record}/edit'),
        ];
    }
}
