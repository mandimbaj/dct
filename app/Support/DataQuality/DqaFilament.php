<?php

namespace App\Support\DataQuality;

use App\Models\Country;
use App\Models\DataSource;
use App\Models\DataQuality\DqaReportModel;
use App\Models\Indicator;
use App\Filament\Resources\HealthIndicatorValues\HealthIndicatorValueResource;
use App\Support\ApprovalWorkflow;
use App\Support\FilamentSearch;
use App\Support\SelectOptions;
use App\Support\TextEncoding;
use App\Support\UserCountryAccess;
use App\Support\UserPermissions;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class DqaFilament
{
    /**
     * @param  class-string  $modelClass
     */
    public static function issueTable(Table $table, string $modelClass, string $remarkColumn, bool $withCounts = false): Table
    {
        $columns = [
            self::column('id', 'id')->sortable()->toggleable(isToggledHiddenByDefault: true),
            self::column('indicator_name', 'indicator')->wrap()->searchable()->sortable(),
            self::column('location', 'country')->searchable()->sortable(),
            self::column('categoryoption', 'category_option')->wrap()->searchable()->toggleable(),
            self::column('datasource', 'source')->wrap()->searchable()->toggleable(),
            self::column('measure_type', 'measure_type')->wrap()->searchable()->toggleable(),
            self::column('value', 'value_received')->searchable()->sortable(),
            self::column('period', 'period')->searchable()->sortable(),
            self::column($remarkColumn, 'remarks')->wrap()->searchable()->sortable(),
        ];

        if ($withCounts) {
            $columns[] = self::column('counts', 'counts')->sortable()->toggleable(isToggledHiddenByDefault: true);
        }

        $searchColumns = [
            'indicator_name',
            'location',
            'categoryoption',
            'datasource',
            'measure_type',
            'value',
            'period',
            $remarkColumn,
        ];

        if ($withCounts) {
            $searchColumns[] = 'counts';
        }

        $numericColumns = $withCounts ? ['id', 'counts'] : ['id'];

        return $table
            ->defaultSort('id', 'desc')
            ->searchUsing(function (Builder $query, string $search) use ($searchColumns, $numericColumns): void {
                FilamentSearch::apply(
                    query: $query,
                    search: $search,
                    columns: $searchColumns,
                    numericColumns: $numericColumns,
                );
            })
            ->columns($columns)
            ->filters(self::issueFilters($modelClass))
            ->recordActions([
                Action::make('correct')
                    ->label(__('aho.actions.correct'))
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->url(fn (DqaReportModel $record): ?string => DqaIssueResolver::correctionUrl($record))
                    ->visible(fn (DqaReportModel $record): bool => (bool) auth()->user()
                        && UserPermissions::allowsResource(auth()->user(), HealthIndicatorValueResource::class, UserPermissions::ACTION_UPDATE)
                        && DqaIssueResolver::correctionUrl($record) !== null),
            ]);
    }

    /**
     * @param  class-string  $modelClass
     * @param  array<string, string>  $columns
     */
    public static function lookupTable(Table $table, string $modelClass, array $columns): Table
    {
        $searchColumns = array_keys($columns);
        $numericColumns = array_values(array_intersect($searchColumns, [
            'id',
            'indicator_id',
            'categoryoption_id',
            'categoryoptionid',
            'datasource_id',
            'datasourceid',
            'measure_type_id',
            'measuremethod_id',
            'user_id',
        ]));

        return $table
            ->defaultSort('id')
            ->searchUsing(function (Builder $query, string $search) use ($searchColumns, $numericColumns): void {
                FilamentSearch::apply(
                    query: $query,
                    search: $search,
                    columns: $searchColumns,
                    numericColumns: $numericColumns,
                );
            })
            ->columns(collect($columns)
                ->map(fn (string $label, string $column): TextColumn => self::column($column, $label)->searchable()->sortable())
                ->values()
                ->all())
            ->filters([
                SelectFilter::make('afrocode')
                    ->label(__('aho.fields.afro_code'))
                    ->options(fn (): array => self::options($modelClass, 'afrocode'))
                    ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::filterAndSort(self::options($modelClass, 'afrocode'), $search))
                    ->searchable(),
            ]);
    }

    public static function factsFilterTable(Table $table): Table
    {
        return $table
            ->defaultSort('filter_id', 'desc')
            ->columns([
                self::column('filter_id', 'id')->sortable(),
                self::column('start_period', 'start')->sortable()->searchable(),
                self::column('end_period', 'end')->sortable()->searchable(),
                self::column('user_id', 'user')->sortable()->searchable(),
            ]);
    }

    public static function factsDatasetTable(Table $table): Table
    {
        return $table
            ->defaultSort('fact_id', 'desc')
            ->searchUsing(function (Builder $query, string $search): void {
                FilamentSearch::apply(
                    query: $query,
                    search: $search,
                    columns: ['period', 'comment', 'string_value'],
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
                    numericColumns: ['fact_id', 'value_received', 'numerator_value', 'denominator_value', 'target_value'],
                );
            })
            ->columns([
                self::column('fact_id', 'id')->sortable()->toggleable(),
                self::column('indicator.afrocode', 'code')->searchable()->sortable(),
                self::column('indicator.display_name', 'indicator')->wrap()->toggleable(),
                self::column('location.display_name', 'location')->toggleable(),
                self::column('period', 'period')->searchable()->sortable(),
                self::column('categoryOption.display_name', 'category_option')->wrap()->toggleable(),
                self::column('dataSource.display_name', 'source')->wrap()->toggleable(),
                self::column('measureMethod.display_name', 'measure_type')->wrap()->toggleable(),
                self::column('value_received', 'value_received')->sortable(),
                self::column('string_value', 'text_value')->wrap()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('comment')
                    ->label(__('aho.fields.approval_status'))
                    ->badge()
                    ->color(fn (?string $state): string => ApprovalWorkflow::color($state))
                    ->formatStateUsing(fn (?string $state): string => ApprovalWorkflow::label($state))
                    ->sortable(),
                self::column('date_created', 'creation')->dateTime()->sortable()->toggleable(),
                self::column('date_lastupdated', 'modification')->dateTime()->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('indicator_id')
                    ->label(__('aho.fields.indicator'))
                    ->relationship('indicator', 'afrocode', modifyQueryUsing: fn (Builder $query): Builder => SelectOptions::orderByDisplayName($query, 'afrocode'))
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)
                    ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::fromDisplayNameQuery(Indicator::query(), $search, 'indicator_id'))
                    ->searchable(),
                SelectFilter::make('location_id')
                    ->label(__('aho.fields.location'))
                    ->relationship('location', 'code', modifyQueryUsing: fn (Builder $query): Builder => UserCountryAccess::scopeLocations(
                        SelectOptions::orderByDisplayName($query->with('translations'), 'code'),
                    ))
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)
                    ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::fromDisplayNameQuery(
                        UserCountryAccess::scopeLocations(Country::query()),
                        $search,
                        'location_id',
                    ))
                    ->searchable(),
                SelectFilter::make('datasource_id')
                    ->label(__('aho.fields.source'))
                    ->relationship('dataSource', 'code', modifyQueryUsing: fn (Builder $query): Builder => SelectOptions::orderByDisplayName($query, 'code'))
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)
                    ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::fromDisplayNameQuery(DataSource::query(), $search, 'datasource_id'))
                    ->searchable(),
                SelectFilter::make('comment')
                    ->label(__('aho.fields.approval_status'))
                    ->options(fn (): array => ApprovalWorkflow::options()),
            ]);
    }

    public static function scopeIssueQuery(Builder $query): Builder
    {
        if (UserCountryAccess::canViewAllCountries()) {
            return $query;
        }

        $terms = self::countryTerms();

        if ($terms === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn($query->getModel()->qualifyColumn('location'), $terms);
    }

    public static function scopeFactsDatasetQuery(Builder $query): Builder
    {
        return UserCountryAccess::scope($query->with([
            'indicator.translations',
            'location.translations',
            'categoryOption.translations',
            'dataSource.translations',
            'measureMethod.translations',
        ]));
    }

    private static function column(string $name, string $label): TextColumn
    {
        return TextColumn::make($name)
            ->label(__("aho.fields.{$label}"))
            ->formatStateUsing(fn (mixed $state): string => self::format($state))
            ->tooltip(fn (mixed $state): ?string => self::tooltip($state));
    }

    /**
     * @param  class-string  $modelClass
     * @return array<int, SelectFilter>
     */
    private static function issueFilters(string $modelClass): array
    {
        return [
            SelectFilter::make('location')
                ->label(__('aho.fields.country'))
                ->options(fn (): array => self::options($modelClass, 'location'))
                ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::filterAndSort(self::options($modelClass, 'location'), $search))
                ->searchable(),
            SelectFilter::make('indicator_name')
                ->label(__('aho.fields.indicator'))
                ->options(fn (): array => self::options($modelClass, 'indicator_name'))
                ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::filterAndSort(self::options($modelClass, 'indicator_name'), $search))
                ->searchable(),
            SelectFilter::make('categoryoption')
                ->label(__('aho.fields.category_option'))
                ->options(fn (): array => self::options($modelClass, 'categoryoption'))
                ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::filterAndSort(self::options($modelClass, 'categoryoption'), $search))
                ->searchable(),
            SelectFilter::make('datasource')
                ->label(__('aho.fields.source'))
                ->options(fn (): array => self::options($modelClass, 'datasource'))
                ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::filterAndSort(self::options($modelClass, 'datasource'), $search))
                ->searchable(),
            SelectFilter::make('measure_type')
                ->label(__('aho.fields.measure_type'))
                ->options(fn (): array => self::options($modelClass, 'measure_type'))
                ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::filterAndSort(self::options($modelClass, 'measure_type'), $search))
                ->searchable(),
        ];
    }

    /**
     * @param  class-string  $modelClass
     * @return array<string, string>
     */
    private static function options(string $modelClass, string $column): array
    {
        $scope = UserCountryAccess::canViewAllCountries()
            ? 'global'
            : implode('-', UserCountryAccess::allowedLocationIds());

        return Cache::remember("dqa-options:{$scope}:".md5($modelClass.':'.$column), now()->addMinutes(20), function () use ($modelClass, $column): array {
            /** @var Builder $query */
            $query = $modelClass::query()
                ->select($column)
                ->whereNotNull($column)
                ->where($column, '<>', '')
                ->distinct()
                ->orderBy($column)
                ->limit(SelectOptions::LIMIT);

            if (is_subclass_of($modelClass, DqaReportModel::class)) {
                self::scopeIssueQuery($query);
            }

            return $query
                ->pluck($column)
                ->map(fn (mixed $value): string => self::format($value))
                ->filter(fn (string $value): bool => $value !== '')
                ->mapWithKeys(fn (string $value): array => [$value => $value])
                ->all();
        });
    }

    /**
     * @return array<int, string>
     */
    private static function countryTerms(): array
    {
        $locationIds = UserCountryAccess::allowedLocationIds();

        if ($locationIds === []) {
            return [];
        }

        return Cache::remember('dqa-country-terms:'.md5(implode('-', $locationIds)), now()->addMinutes(20), function () use ($locationIds): array {
            return Country::query()
                ->with('translations')
                ->whereIn('location_id', $locationIds)
                ->get()
                ->flatMap(function (Country $country): array {
                    return [
                        $country->code,
                        $country->iso_alpha,
                        $country->display_name,
                        ...$country->translations->pluck('name')->all(),
                    ];
                })
                ->map(fn (mixed $value): string => self::format($value))
                ->filter(fn (string $value): bool => $value !== '')
                ->flatMap(fn (string $value): array => [$value, strtoupper($value), strtolower($value)])
                ->unique()
                ->values()
                ->all();
        });
    }

    private static function format(mixed $state): string
    {
        if ($state === null) {
            return '';
        }

        if (is_bool($state)) {
            return $state ? __('aho.fields.yes') : __('aho.fields.no');
        }

        return TextEncoding::clean((string) $state) ?? (string) $state;
    }

    private static function tooltip(mixed $state): ?string
    {
        $value = self::format($state);

        return mb_strlen($value) > 80 ? $value : null;
    }
}
