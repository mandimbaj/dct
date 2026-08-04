<?php

namespace App\Filament\Resources\HealthServiceValues;

use App\Filament\Clusters\HealthServices;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\HealthServiceValues\Pages\CreateHealthServiceValue;
use App\Filament\Resources\HealthServiceValues\Pages\EditHealthServiceValue;
use App\Filament\Resources\HealthServiceValues\Pages\ListHealthServiceValues;
use App\Models\Country;
use App\Models\DataSource;
use App\Models\HealthServiceValue;
use App\Models\Indicator;
use App\Models\IndicatorCategory;
use App\Models\MeasureMethod;
use App\Models\TimePeriod;
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
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
 * Health-service fact-value resource.
 *
 * The resource is scoped by country and reuses shared indicator, category, source and measure
 * method references from the warehouse.
 */
class HealthServiceValueResource extends Resource
{
    protected static ?string $model = HealthServiceValue::class;

    protected static ?string $cluster = HealthServices::class;

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
        return __('aho.resources.health_service_values.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.health_service_values.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.health_service_values.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make(__('aho.form_sections.indicator_details'))
                    ->schema([
                        Select::make('indicator_id')
                            ->label(__('aho.fields.indicator'))
                            ->relationship('indicator', 'afrocode', modifyQueryUsing: fn (Builder $query): Builder => self::hscIndicatorQuery($query->with('translations')))
                            ->getOptionLabelFromRecordUsing(fn (Indicator $record): string => $record->display_name)
                            ->options(fn (): array => SelectOptions::fromDisplayNameQuery(self::hscIndicatorQuery(Indicator::query()), keyName: 'indicator_id'))
                            ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::fromDisplayNameQuery(self::hscIndicatorQuery(Indicator::query()), $search, 'indicator_id'))
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

                        Select::make('measuremethod_id')
                            ->label(__('aho.fields.measure_method'))
                            ->relationship('measureMethod', 'code', modifyQueryUsing: fn (Builder $query): Builder => SelectOptions::orderByDisplayName($query->with('translations'), 'code'))
                            ->getOptionLabelFromRecordUsing(fn (MeasureMethod $record): string => $record->display_name)
                            ->options(fn (): array => SelectOptions::fromDisplayNameQuery(MeasureMethod::query(), keyName: 'measuremethod_id'))
                            ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::fromDisplayNameQuery(MeasureMethod::query(), $search, 'measuremethod_id'))
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make(__('aho.form_sections.data_values'))
                    ->schema([
                        TextInput::make('value_received')->label(__('aho.fields.value_received'))->numeric(),
                        TextInput::make('numerator_value')->label(__('aho.fields.numerator'))->numeric(),
                        TextInput::make('denominator_value')->label(__('aho.fields.denominator'))->numeric(),
                        TextInput::make('min_value')->label(__('aho.fields.min'))->numeric(),
                        TextInput::make('max_value')->label(__('aho.fields.max'))->numeric(),
                        TextInput::make('target_value')->label(__('aho.fields.target'))->numeric(),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),

                Section::make(__('aho.form_sections.reporting_period'))
                    ->schema([
                        Select::make('periodicity_id')
                            ->label(__('aho.fields.period_type'))
                            ->relationship('period', 'name', modifyQueryUsing: fn (Builder $query): Builder => $query->orderBy('period_id'))
                            ->getOptionLabelFromRecordUsing(fn (TimePeriod $record): string => $record->display_name)
                            ->options(fn (): array => TimePeriod::query()->orderBy('period_id')->pluck('name', 'period_id')->all())
                            ->default(1)
                            ->searchable()
                            ->required(),
                        DatePicker::make('start_period')
                            ->label(__('aho.fields.start'))
                            ->maxDate(now())
                            ->required(),
                        DatePicker::make('end_period')
                            ->label(__('aho.fields.end'))
                            ->maxDate(now())
                            ->rules(['after_or_equal:start_period']),
                        Toggle::make('has_lastdate')
                            ->label(__('aho.fields.has_lastdate')),
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
                    columns: ['period', 'comment'],
                    relations: [
                        'indicator' => ['afrocode', 'gen_code'],
                        'indicator.translations' => ['name', 'shortname', 'definition'],
                        'location' => ['code', 'iso_alpha', 'iso_number'],
                        'location.translations' => ['name'],
                        'categoryOption' => ['code'],
                        'categoryOption.translations' => ['name'],
                        'dataSource' => ['code'],
                        'dataSource.translations' => ['name'],
                        'measureMethod' => ['code'],
                        'measureMethod.translations' => ['name'],
                    ],
                    numericColumns: ['fact_id', 'value_received', 'value_calculated'],
                );
            })
            ->columns([
                TextColumn::make('fact_id')->label(__('aho.fields.id'))->sortable()->toggleable(),
                TextColumn::make('indicator.afrocode')->label(__('aho.fields.code'))->searchable()->sortable(),
                TextColumn::make('indicator.display_name')->label(__('aho.fields.indicator'))->wrap()->toggleable(),
                TextColumn::make('location.display_name')->label(__('aho.fields.location'))->toggleable(),
                TextColumn::make('period')->label(__('aho.fields.period'))->sortable()->searchable(),
                TextColumn::make('value_received')->label(__('aho.fields.value_received'))->numeric()->sortable(),
                TextColumn::make('value_calculated')->label(__('aho.fields.value_calculated'))->numeric()->sortable()->toggleable(),
                TextColumn::make('dataSource.display_name')->label(__('aho.fields.source'))->toggleable(),
                TextColumn::make('comment')
                    ->label(__('aho.fields.approval_status'))
                    ->badge()
                    ->color(fn (?string $state): string => StatusColor::for($state))
                    ->formatStateUsing(fn (?string $state): string => ApprovalWorkflow::label($state))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('date_created')->label(__('aho.fields.creation'))->dateTime()->sortable()->toggleable(),
                TextColumn::make('date_lastupdated')->label(__('aho.fields.modification'))->dateTime()->sortable()->toggleable(),
                TextColumn::make('uploadedBy.name')
                    ->label(__('aho.fields.uploaded_by'))
                    ->state(fn (HealthServiceValue $record): string => UserDisplayName::uploadedBy(
                        $record->uploadedBy,
                        $record->warehouseUploadedBy,
                        $record->user_id,
                    ))
                    ->tooltip(fn (HealthServiceValue $record): ?string => UserDisplayName::uploadedByTooltip(
                        $record->uploadedBy,
                        $record->warehouseUploadedBy,
                        $record->user_id,
                    ))
                    ->visible(fn (): bool => UserDisplayName::canViewUploaders())
                    ->wrap()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('indicator_id')
                    ->label(__('aho.fields.indicator'))
                    ->relationship('indicator', 'afrocode', modifyQueryUsing: fn (Builder $query): Builder => SelectOptions::orderByDisplayName($query, 'afrocode'))
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)
                    ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::fromDisplayNameQuery(Indicator::query(), $search, 'indicator_id'))
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
                'indicator.translations',
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
            'index' => ListHealthServiceValues::route('/'),
            'create' => CreateHealthServiceValue::route('/create'),
            'edit' => EditHealthServiceValue::route('/{record}/edit'),
        ];
    }

    private static function hscIndicatorQuery(Builder $query): Builder
    {
        return $query
            ->whereHas('reference', function (Builder $query): Builder {
                return $query->where('code', 'GIR0005')->orWhere('reference_id', 5);
            })
            ->tap(fn (Builder $query): Builder => SelectOptions::orderByDisplayName($query, 'afrocode'));
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
