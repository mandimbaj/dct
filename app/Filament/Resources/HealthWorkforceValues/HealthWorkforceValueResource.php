<?php

namespace App\Filament\Resources\HealthWorkforceValues;

use App\Filament\Clusters\HealthWorkforce;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\HealthWorkforceValues\Pages\CreateHealthWorkforceValue;
use App\Filament\Resources\HealthWorkforceValues\Pages\EditHealthWorkforceValue;
use App\Filament\Resources\HealthWorkforceValues\Pages\ListHealthWorkforceValues;
use App\Models\Country;
use App\Models\DataSource;
use App\Models\HealthCadre;
use App\Models\HealthWorkforceValue;
use App\Models\IndicatorCategory;
use App\Models\MeasureMethod;
use App\Support\ApprovalWorkflow;
use App\Support\CountryTableFilter;
use App\Support\FilamentSearch;
use App\Support\SelectOptions;
use App\Support\StatusColor;
use App\Support\UserCountryAccess;
use App\Support\UserDisplayName;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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

class HealthWorkforceValueResource extends Resource
{
    protected static ?string $model = HealthWorkforceValue::class;

    protected static ?string $cluster = HealthWorkforce::class;

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
        return __('aho.resources.health_workforce_values.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.health_workforce_values.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.health_workforce_values.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make(__('aho.form_sections.primary_attributes'))
                    ->schema([
                        Select::make('cadre_id')
                            ->label(__('aho.fields.cadre'))
                            ->relationship('cadre', 'code', modifyQueryUsing: fn (Builder $query): Builder => SelectOptions::orderByDisplayName($query->with('translations'), 'code'))
                            ->getOptionLabelFromRecordUsing(fn (HealthCadre $record): string => $record->display_name)
                            ->options(fn (): array => SelectOptions::fromDisplayNameQuery(HealthCadre::query(), keyName: 'cadre_id'))
                            ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::fromDisplayNameQuery(HealthCadre::query(), $search, 'cadre_id'))
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('categoryoption_id')
                            ->label(__('aho.fields.category_option'))
                            ->relationship('categoryOption', 'code', modifyQueryUsing: fn (Builder $query): Builder => $query
                                ->with(['translations', 'parentCategory.translations'])
                                ->tap(fn (Builder $query): Builder => SelectOptions::orderByDisplayName($query, 'code')))
                            ->getOptionLabelFromRecordUsing(fn (IndicatorCategory $record): string => self::categoryOptionLabel($record))
                            ->options(fn (): array => self::categoryOptionOptions())
                            ->getSearchResultsUsing(fn (?string $search): array => self::categoryOptionSearchResults($search))
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('datasource_id')
                            ->label(__('aho.fields.source'))
                            ->relationship('dataSource', 'code', modifyQueryUsing: fn (Builder $query): Builder => SelectOptions::orderByDisplayName($query->with('translations'), 'code'))
                            ->getOptionLabelFromRecordUsing(fn (DataSource $record): string => $record->display_name)
                            ->options(fn (): array => SelectOptions::fromDisplayNameQuery(DataSource::query(), keyName: 'datasource_id'))
                            ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::fromDisplayNameQuery(DataSource::query(), $search, 'datasource_id'))
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
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make(__('aho.form_sections.reporting_period_values'))
                    ->schema([
                        TextInput::make('start_year')
                            ->label(__('aho.fields.start'))
                            ->numeric()
                            ->minValue(1900)
                            ->maxValue((int) date('Y'))
                            ->required(),

                        TextInput::make('end_year')
                            ->label(__('aho.fields.end'))
                            ->numeric()
                            ->minValue(1900)
                            ->maxValue((int) date('Y'))
                            ->rules(['gte:start_year'])
                            ->required(),

                        Select::make('measuremethod_id')
                            ->label(__('aho.fields.measure_method'))
                            ->relationship('measureMethod', 'code', modifyQueryUsing: fn (Builder $query): Builder => SelectOptions::orderByDisplayName($query->with('translations'), 'code'))
                            ->getOptionLabelFromRecordUsing(fn (MeasureMethod $record): string => $record->display_name)
                            ->options(fn (): array => SelectOptions::fromDisplayNameQuery(MeasureMethod::query(), keyName: 'measuremethod_id'))
                            ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::fromDisplayNameQuery(MeasureMethod::query(), $search, 'measuremethod_id'))
                            ->searchable()
                            ->preload(),

                        TextInput::make('value')
                            ->label(__('aho.fields.value'))
                            ->numeric()
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
                    columns: ['period', 'status'],
                    relations: [
                        'cadre' => ['code'],
                        'cadre.translations' => ['name'],
                        'location' => ['code', 'iso_alpha', 'iso_number'],
                        'location.translations' => ['name'],
                        'categoryOption' => ['code'],
                        'categoryOption.translations' => ['name'],
                        'dataSource' => ['code'],
                        'dataSource.translations' => ['name'],
                        'measureMethod' => ['code'],
                        'measureMethod.translations' => ['name'],
                    ],
                    numericColumns: ['fact_id', 'value'],
                );
            })
            ->columns([
                TextColumn::make('fact_id')->label(__('aho.fields.id'))->sortable()->toggleable(),
                TextColumn::make('cadre.display_name')->label(__('aho.fields.cadre'))->wrap(),
                TextColumn::make('location.display_name')->label(__('aho.fields.location'))->toggleable(),
                TextColumn::make('period')->label(__('aho.fields.period'))->sortable()->searchable(),
                TextColumn::make('categoryOption.display_name')->label(__('aho.fields.disaggregation'))->toggleable(),
                TextColumn::make('dataSource.display_name')->label(__('aho.fields.source'))->toggleable(),
                TextColumn::make('value')->label(__('aho.fields.value'))->numeric()->sortable(),
                TextColumn::make('status')
                    ->label(__('aho.fields.status'))
                    ->badge()
                    ->color(fn (?string $state): string => StatusColor::for($state))
                    ->formatStateUsing(fn (?string $state): string => ApprovalWorkflow::label($state))
                    ->toggleable(),
                TextColumn::make('date_created')->label(__('aho.fields.creation'))->dateTime()->sortable()->toggleable(),
                TextColumn::make('date_lastupdated')->label(__('aho.fields.modification'))->dateTime()->sortable()->toggleable(),
                TextColumn::make('uploadedBy.name')
                    ->label(__('aho.fields.uploaded_by'))
                    ->state(fn (HealthWorkforceValue $record): string => UserDisplayName::uploadedBy(
                        $record->uploadedBy,
                        $record->warehouseUploadedBy,
                        $record->user_id,
                    ))
                    ->tooltip(fn (HealthWorkforceValue $record): ?string => UserDisplayName::uploadedByTooltip(
                        $record->uploadedBy,
                        $record->warehouseUploadedBy,
                        $record->user_id,
                    ))
                    ->visible(fn (): bool => UserDisplayName::canViewUploaders())
                    ->wrap()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('cadre_id')
                    ->label(__('aho.fields.cadre'))
                    ->relationship('cadre', 'code', modifyQueryUsing: fn (Builder $query): Builder => SelectOptions::orderByDisplayName($query, 'code'))
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)
                    ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::fromDisplayNameQuery(HealthCadre::query(), $search, 'cadre_id'))
                    ->searchable(),
                CountryTableFilter::make(),
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
        return UserCountryAccess::scope(
            parent::getEloquentQuery()->with([
                'cadre.translations',
                'location.translations',
                'categoryOption.translations',
                'dataSource.translations',
                'measureMethod.translations',
                'uploadedBy',
                'warehouseUploadedBy',
            ]),
        );
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHealthWorkforceValues::route('/'),
            'create' => CreateHealthWorkforceValue::route('/create'),
            'edit' => EditHealthWorkforceValue::route('/{record}/edit'),
        ];
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
}
