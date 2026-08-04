<?php

namespace App\Services\UhcClock;

use App\Models\Country;
use App\Models\User;
use App\Models\WarehouseAuthenticationUser;
use App\Support\TextEncoding;
use App\Support\UserCountryAccess;
use App\Support\UserDisplayName;
use App\Support\WarehouseLocale;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UhcTargetAttainmentService
{
    private const LIVE_TABLE = 'fact_data_indicators';

    private const ARCHIVE_TABLE = 'fact_data_archive';

    private const BASELINE_YEAR = 2016;

    private const LEVELS = ['day', 'hour', 'minute', 'second'];

    private const LOWER_IS_BETTER_CODES = [
        'AFR0008',
        'AFR0010',
        'AFR0012',
        'AFR0016',
        'AFR0017',
        'AFR0104',
        'AFR0106',
        'AFR0107',
        'AFR0109',
        'AFR0110',
        'AFR0132',
        'AFR0215',
        'AFR0259',
        'AFR0269',
        'AFR0307',
        'AFR0310',
        'AFR0325',
        'AFR0331',
        'AFR0334',
        'AFR0352',
        'AFR0394',
        'AFR0401',
        'AFR0441',
    ];

    /**
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        $selections = $this->selectedIndicators();

        if ($selections->isEmpty()) {
            return [
                'selected' => 0,
                'assessed' => 0,
                'achieved' => 0,
                'below_target' => 0,
                'not_evaluable' => 0,
                'achievement_rate' => null,
                'target_evaluable' => 0,
                'no_target' => 0,
                'levels' => $this->emptyLevels(),
                'countries' => [],
            ];
        }

        $facts = $this->facts(
            $selections->pluck('location_id')->unique()->values(),
            $selections->pluck('indicator_id')->unique()->values(),
        );

        $countries = Country::query()
            ->with('translations')
            ->whereIn('location_id', $selections->pluck('location_id')->unique())
            ->get()
            ->keyBy('location_id');

        $countryRows = $selections
            ->groupBy('location_id')
            ->map(function (Collection $countrySelections, int $locationId) use ($facts, $countries): array {
                $evaluations = $countrySelections
                    ->map(fn (object $selection): array => $this->evaluateSelection($selection, $facts))
                    ->values();

                return array_merge($this->summarizeEvaluations($evaluations), [
                    'country' => $countries->get($locationId)?->display_name ?? (string) $locationId,
                    'location_id' => $locationId,
                    'details' => $this->countryDetails($evaluations),
                ]);
            })
            ->sortBy(fn (array $row): string => mb_strtolower($row['country']))
            ->values();

        $allEvaluations = $selections->map(fn (object $selection): array => $this->evaluateSelection($selection, $facts));
        $summary = $this->summarizeEvaluations($allEvaluations);

        return array_merge($summary, [
            'countries' => $countryRows->all(),
        ]);
    }

    /**
     * @return Collection<int, object>
     */
    private function selectedIndicators(): Collection
    {
        $locale = WarehouseLocale::current();

        $query = DB::connection('warehouse')
            ->table('stg_uhclock_country_indicators_selection as selections')
            ->join(
                'stg_uhclock_country_indicators_selection_indicators as selection_indicators',
                'selection_indicators.countryselectionuhcindicators_id',
                '=',
                'selections.countrychoice_id',
            )
            ->join('stg_uhclock_indicators as uhc_indicators', 'uhc_indicators.id', '=', 'selection_indicators.stguhclockindicators_id')
            ->leftJoin('stg_indicator as indicators', 'indicators.indicator_id', '=', 'uhc_indicators.indicator_id')
            ->leftJoin('stg_indicator_translation as preferred_indicator_translations', function ($join) use ($locale): void {
                $join
                    ->on('preferred_indicator_translations.master_id', '=', 'indicators.indicator_id')
                    ->where('preferred_indicator_translations.language_code', '=', $locale);
            })
            ->leftJoin('stg_indicator_translation as english_indicator_translations', function ($join): void {
                $join
                    ->on('english_indicator_translations.master_id', '=', 'indicators.indicator_id')
                    ->where('english_indicator_translations.language_code', '=', 'en');
            })
            ->leftJoin('stg_uhclock_indicator_groups as groups', 'groups.group_id', '=', 'uhc_indicators.group_id')
            ->leftJoin('stg_uhclock_indicator_groups_translation as preferred_group_translations', function ($join) use ($locale): void {
                $join
                    ->on('preferred_group_translations.master_id', '=', 'groups.group_id')
                    ->where('preferred_group_translations.language_code', '=', $locale);
            })
            ->leftJoin('stg_uhclock_indicator_groups_translation as english_group_translations', function ($join): void {
                $join
                    ->on('english_group_translations.master_id', '=', 'groups.group_id')
                    ->where('english_group_translations.language_code', '=', 'en');
            })
            ->select([
                'selections.location_id',
                'uhc_indicators.id as uhc_indicator_id',
                'uhc_indicators.indicator_id',
                'uhc_indicators.Indicator_type as indicator_type',
                'uhc_indicators.group_id',
                'indicators.afrocode as indicator_code',
                DB::raw('coalesce(preferred_indicator_translations.name, english_indicator_translations.name, indicators.afrocode) as indicator_name'),
                'english_indicator_translations.name as indicator_name_en',
                DB::raw('coalesce(preferred_group_translations.name, english_group_translations.name) as group_name'),
            ])
            ->distinct();

        if (! UserCountryAccess::canViewAllCountries()) {
            $locationIds = UserCountryAccess::allowedLocationIds();

            $locationIds === []
                ? $query->whereRaw('1 = 0')
                : $query->whereIn('selections.location_id', $locationIds);
        }

        return $query->get()
            ->map(function (object $selection): object {
                $selection->indicator_name = TextEncoding::clean($selection->indicator_name) ?? $selection->indicator_name;
                $selection->indicator_name_en = TextEncoding::clean($selection->indicator_name_en) ?? $selection->indicator_name_en;
                $selection->group_name = TextEncoding::clean($selection->group_name) ?? $selection->group_name;

                return $selection;
            });
    }

    /**
     * @param  Collection<int, int>  $locationIds
     * @param  Collection<int, int>  $indicatorIds
     * @return Collection<string, Collection<int, object>>
     */
    private function facts(Collection $locationIds, Collection $indicatorIds): Collection
    {
        $facts = collect($this->factTables())
            ->flatMap(fn (array $table): Collection => $this->factRowsForTable($table, $locationIds, $indicatorIds))
            ->groupBy(fn (object $fact): string => filled($fact->uuid) ? 'uuid:'.$fact->uuid : $fact->source.':'.$fact->fact_id)
            ->map(fn (Collection $duplicates): object => $this->preferredDuplicate($duplicates))
            ->values();

        return $this->withUploaderNames($facts)
            ->groupBy(fn (object $fact): string => $this->factKey((int) $fact->location_id, (int) $fact->indicator_id));
    }

    /**
     * @return array<int, array{table: string, source: string}>
     */
    private function factTables(): array
    {
        return Schema::connection('warehouse')->hasTable(self::ARCHIVE_TABLE)
            ? [
                ['table' => self::LIVE_TABLE, 'source' => 'active'],
                ['table' => self::ARCHIVE_TABLE, 'source' => 'archive'],
            ]
            : [
                ['table' => self::LIVE_TABLE, 'source' => 'active'],
            ];
    }

    /**
     * @param  array{table: string, source: string}  $table
     * @param  Collection<int, int>  $locationIds
     * @param  Collection<int, int>  $indicatorIds
     * @return Collection<int, object>
     */
    private function factRowsForTable(array $table, Collection $locationIds, Collection $indicatorIds): Collection
    {
        $tableName = $table['table'];
        $locale = WarehouseLocale::current();
        $hasDatasourceColumn = Schema::connection('warehouse')->hasColumn($tableName, 'datasource_id');
        $hasDatasourceTable = Schema::connection('warehouse')->hasTable('stg_datasource');
        $hasDatasourceTranslations = Schema::connection('warehouse')->hasTable('stg_datasource_translation');

        $query = DB::connection('warehouse')
            ->table($tableName)
            ->whereIn("{$tableName}.location_id", $locationIds)
            ->whereIn("{$tableName}.indicator_id", $indicatorIds)
            ->whereNotNull("{$tableName}.value_received")
            ->where("{$tableName}.end_period", '>=', self::BASELINE_YEAR);

        if ($hasDatasourceColumn && $hasDatasourceTable) {
            $query->leftJoin('stg_datasource as datasources', 'datasources.datasource_id', '=', "{$tableName}.datasource_id");
        }

        if ($hasDatasourceColumn && $hasDatasourceTranslations) {
            $query
                ->leftJoin('stg_datasource_translation as preferred_source_translations', function ($join) use ($tableName, $locale): void {
                    $join
                        ->on('preferred_source_translations.master_id', '=', "{$tableName}.datasource_id")
                        ->where('preferred_source_translations.language_code', '=', $locale);
                })
                ->leftJoin('stg_datasource_translation as english_source_translations', function ($join) use ($tableName): void {
                    $join
                        ->on('english_source_translations.master_id', '=', "{$tableName}.datasource_id")
                        ->where('english_source_translations.language_code', '=', 'en');
                });
        }

        if ($tableName === self::ARCHIVE_TABLE) {
            $query = $this->withoutActiveDuplicate($query, $tableName);
        }

        $uuidColumn = Schema::connection('warehouse')->hasColumn($tableName, 'uuid')
            ? "{$tableName}.uuid"
            : DB::raw('null as uuid');

        $columns = [
            "{$tableName}.fact_id",
            $uuidColumn,
            "{$tableName}.location_id",
            "{$tableName}.indicator_id",
            "{$tableName}.value_received",
            "{$tableName}.target_value",
            "{$tableName}.period",
            "{$tableName}.start_period",
            "{$tableName}.end_period",
            "{$tableName}.date_lastupdated",
            DB::raw("'{$table['source']}' as source"),
        ];

        if ($hasDatasourceColumn) {
            $columns[] = "{$tableName}.datasource_id";
        } else {
            $columns[] = DB::raw('null as datasource_id');
        }

        $columns[] = Schema::connection('warehouse')->hasColumn($tableName, 'user_id')
            ? "{$tableName}.user_id"
            : DB::raw('null as user_id');

        if ($hasDatasourceColumn && $hasDatasourceTranslations) {
            $fallbackSourceName = $hasDatasourceTable ? 'datasources.code' : "{$tableName}.datasource_id";
            $columns[] = DB::raw("coalesce(preferred_source_translations.name, preferred_source_translations.shortname, english_source_translations.name, english_source_translations.shortname, {$fallbackSourceName}) as datasource_name");
            $columns[] = DB::raw('coalesce(preferred_source_translations.level, english_source_translations.level) as datasource_level');
        } else {
            $columns[] = $hasDatasourceColumn && $hasDatasourceTable
                ? DB::raw('datasources.code as datasource_name')
                : DB::raw('null as datasource_name');
            $columns[] = DB::raw('null as datasource_level');
        }

        return $query
            ->get($columns)
            ->map(function (object $fact): object {
                $fact->datasource_name = TextEncoding::clean($fact->datasource_name) ?? $fact->datasource_name;
                $fact->datasource_level = TextEncoding::clean($fact->datasource_level) ?? $fact->datasource_level;
                $fact->datasource_category = $this->dataSourceCategory($fact);

                return $fact;
            });
    }

    private function withoutActiveDuplicate(QueryBuilder $query, string $table): QueryBuilder
    {
        if (
            ! Schema::connection('warehouse')->hasColumn(self::LIVE_TABLE, 'uuid')
            || ! Schema::connection('warehouse')->hasColumn(self::ARCHIVE_TABLE, 'uuid')
        ) {
            return $query;
        }

        return $query->where(function (QueryBuilder $archiveQuery) use ($table): void {
            $archiveQuery
                ->whereNull("{$table}.uuid")
                ->orWhereNotExists(function (QueryBuilder $activeQuery) use ($table): void {
                    $activeQuery
                        ->selectRaw('1')
                        ->from(self::LIVE_TABLE.' as active_values')
                        ->whereColumn('active_values.uuid', "{$table}.uuid");
                });
        });
    }

    private function preferredDuplicate(Collection $duplicates): object
    {
        return $duplicates
            ->sort(fn (object $left, object $right): int => $this->factSortValues($right) <=> $this->factSortValues($left))
            ->first();
    }

    /**
     * @param  Collection<int, object>  $facts
     * @return Collection<int, object>
     */
    private function withUploaderNames(Collection $facts): Collection
    {
        if (! UserDisplayName::canViewUploaders()) {
            return $facts->map(function (object $fact): object {
                $fact->uploaded_by = null;
                $fact->uploaded_by_tooltip = null;

                return $fact;
            });
        }

        $userIds = $facts
            ->pluck('user_id')
            ->filter(fn (mixed $userId): bool => filled($userId))
            ->map(fn (mixed $userId): int => (int) $userId)
            ->unique()
            ->values();

        $localUsers = Schema::hasTable('users') && $userIds->isNotEmpty()
            ? User::query()->whereIn('id', $userIds)->get()->keyBy('id')
            : collect();

        $warehouseUsers = Schema::connection('warehouse')->hasTable('authentication_customuser') && $userIds->isNotEmpty()
            ? WarehouseAuthenticationUser::query()->whereIn('id', $userIds)->get()->keyBy('id')
            : collect();

        return $facts->map(function (object $fact) use ($localUsers, $warehouseUsers): object {
            $userId = filled($fact->user_id) ? (int) $fact->user_id : null;
            $localUser = $userId ? $localUsers->get($userId) : null;
            $warehouseUser = $userId ? $warehouseUsers->get($userId) : null;

            $fact->uploaded_by = UserDisplayName::uploadedBy($localUser, $warehouseUser, $userId);
            $fact->uploaded_by_tooltip = UserDisplayName::uploadedByTooltip($localUser, $warehouseUser, $userId);

            return $fact;
        });
    }

    /**
     * @param  Collection<string, Collection<int, object>>  $facts
     * @return array<string, mixed>
     */
    private function evaluateSelection(object $selection, Collection $facts): array
    {
        $level = $this->levelFor($selection);
        $rows = $facts->get($this->factKey((int) $selection->location_id, (int) $selection->indicator_id), collect());
        $baseline = $this->baselineFact($rows);
        $current = $this->currentFact($rows);

        $base = [
            'level' => $level,
            'location_id' => (int) $selection->location_id,
            'indicator_id' => (int) $selection->indicator_id,
            'indicator_code' => $selection->indicator_code,
            'indicator_name' => $selection->indicator_name,
            'indicator_name_en' => $selection->indicator_name_en,
            'group_name' => $selection->group_name,
            'facts_count' => $rows->count(),
            'has_baseline' => (bool) $baseline,
            'has_current' => (bool) $current,
            'missing_reason' => $this->missingReason($rows, $baseline, $current),
            'assessed' => false,
            'change' => null,
            'apc_remaining' => null,
            'target_reached' => null,
        ];

        if (! $baseline || ! $current) {
            return $base;
        }

        $baselineValue = (float) $baseline->value_received;
        $currentValue = (float) $current->value_received;
        $method = $this->changeMethodFor($level, $selection->indicator_code);

        if ($method === 'percent' && abs($baselineValue) < 0.000001) {
            return array_merge($base, [
                'missing_reason' => 'zero_baseline',
            ]);
        }

        $lowerIsBetter = $this->lowerIsBetter($selection);
        $change = $this->change($baselineValue, $currentValue, $lowerIsBetter, $method);
        $targetValue = filled($current->target_value) ? (float) $current->target_value : null;
        $targetReached = null;
        $apcRemaining = null;

        if ($targetValue !== null) {
            $targetChange = $this->change($baselineValue, $targetValue, $lowerIsBetter, $method);
            $apcRemaining = $targetChange - $change;
            $targetReached = $lowerIsBetter
                ? $currentValue <= $targetValue
                : $currentValue >= $targetValue;
        } elseif (in_array($level, ['day', 'hour'], true)) {
            $apcRemaining = 100 - $change;
        }

        return array_merge($base, [
            'assessed' => true,
            'baseline_value' => $baselineValue,
            'baseline_period' => $baseline->period ?: (string) $baseline->end_period,
            'baseline_source' => $baseline->source,
            'baseline_datasource_id' => $baseline->datasource_id,
            'baseline_datasource_name' => $baseline->datasource_name,
            'baseline_datasource_level' => $baseline->datasource_level,
            'baseline_datasource_category' => $baseline->datasource_category,
            'baseline_uploaded_by' => $baseline->uploaded_by,
            'baseline_uploaded_by_tooltip' => $baseline->uploaded_by_tooltip,
            'current_value' => $currentValue,
            'current_period' => $current->period ?: (string) $current->end_period,
            'current_source' => $current->source,
            'current_datasource_id' => $current->datasource_id,
            'current_datasource_name' => $current->datasource_name,
            'current_datasource_level' => $current->datasource_level,
            'current_datasource_category' => $current->datasource_category,
            'current_uploaded_by' => $current->uploaded_by,
            'current_uploaded_by_tooltip' => $current->uploaded_by_tooltip,
            'target_value' => $targetValue,
            'change' => $change,
            'apc_remaining' => $apcRemaining,
            'target_reached' => $targetReached,
        ]);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $evaluations
     * @return array<string, mixed>
     */
    private function countryDetails(Collection $evaluations): array
    {
        return [
            'levels' => collect(self::LEVELS)
                ->mapWithKeys(fn (string $level): array => [
                    $level => $this->summarizeLevel($level, $evaluations->where('level', $level)),
                ])
                ->all(),
            'assessed_examples' => $evaluations
                ->where('assessed', true)
                ->sortBy(fn (array $evaluation): string => mb_strtolower((string) ($evaluation['indicator_name'] ?? $evaluation['indicator_code'] ?? '')))
                ->take(5)
                ->values()
                ->map(fn (array $evaluation): array => $this->detailEvaluation($evaluation))
                ->all(),
            'missing_examples' => $evaluations
                ->where('assessed', false)
                ->sortBy(fn (array $evaluation): string => mb_strtolower((string) ($evaluation['indicator_name'] ?? $evaluation['indicator_code'] ?? '')))
                ->take(5)
                ->values()
                ->map(fn (array $evaluation): array => $this->detailEvaluation($evaluation))
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function detailEvaluation(array $evaluation): array
    {
        return [
            'level' => $evaluation['level'],
            'indicator_code' => $evaluation['indicator_code'],
            'indicator_name' => $evaluation['indicator_name'],
            'indicator_name_en' => $evaluation['indicator_name_en'] ?? $evaluation['indicator_name'],
            'group_name' => $evaluation['group_name'],
            'facts_count' => $evaluation['facts_count'] ?? 0,
            'missing_reason' => $evaluation['missing_reason'] ?? null,
            'baseline_period' => $evaluation['baseline_period'] ?? null,
            'baseline_value' => $evaluation['baseline_value'] ?? null,
            'baseline_source' => $evaluation['baseline_source'] ?? null,
            'baseline_datasource_id' => $evaluation['baseline_datasource_id'] ?? null,
            'baseline_datasource_name' => $evaluation['baseline_datasource_name'] ?? null,
            'baseline_datasource_level' => $evaluation['baseline_datasource_level'] ?? null,
            'baseline_datasource_category' => $evaluation['baseline_datasource_category'] ?? null,
            'baseline_uploaded_by' => $evaluation['baseline_uploaded_by'] ?? null,
            'baseline_uploaded_by_tooltip' => $evaluation['baseline_uploaded_by_tooltip'] ?? null,
            'current_period' => $evaluation['current_period'] ?? null,
            'current_value' => $evaluation['current_value'] ?? null,
            'current_source' => $evaluation['current_source'] ?? null,
            'current_datasource_id' => $evaluation['current_datasource_id'] ?? null,
            'current_datasource_name' => $evaluation['current_datasource_name'] ?? null,
            'current_datasource_level' => $evaluation['current_datasource_level'] ?? null,
            'current_datasource_category' => $evaluation['current_datasource_category'] ?? null,
            'current_uploaded_by' => $evaluation['current_uploaded_by'] ?? null,
            'current_uploaded_by_tooltip' => $evaluation['current_uploaded_by_tooltip'] ?? null,
            'target_value' => $evaluation['target_value'] ?? null,
            'change' => $evaluation['change'] ?? null,
            'apc_remaining' => $evaluation['apc_remaining'] ?? null,
            'target_reached' => $evaluation['target_reached'] ?? null,
        ];
    }

    private function missingReason(Collection $rows, ?object $baseline, ?object $current): ?string
    {
        if ($baseline && $current) {
            return null;
        }

        if ($rows->isEmpty()) {
            return 'no_values';
        }

        if (! $baseline && ! $current) {
            return 'missing_baseline_and_current';
        }

        return $baseline ? 'missing_recent' : 'missing_baseline';
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $evaluations
     * @return array<string, mixed>
     */
    private function summarizeEvaluations(Collection $evaluations): array
    {
        $assessed = $evaluations->where('assessed', true);
        $targetEvaluable = $evaluations->filter(fn (array $evaluation): bool => $evaluation['target_reached'] !== null);
        $achieved = $targetEvaluable->where('target_reached', true)->count();

        return [
            'selected' => $evaluations->count(),
            'assessed' => $assessed->count(),
            'achieved' => $achieved,
            'below_target' => $targetEvaluable->where('target_reached', false)->count(),
            'target_evaluable' => $targetEvaluable->count(),
            'no_target' => $assessed->count() - $targetEvaluable->count(),
            'not_evaluable' => $evaluations->count() - $assessed->count(),
            'achievement_rate' => $targetEvaluable->isNotEmpty() ? ($achieved / $targetEvaluable->count()) * 100 : null,
            'levels' => collect(self::LEVELS)
                ->mapWithKeys(fn (string $level): array => [
                    $level => $this->summarizeLevel($level, $evaluations->where('level', $level)),
                ])
                ->all(),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $evaluations
     * @return array<string, mixed>
     */
    private function summarizeLevel(string $level, Collection $evaluations): array
    {
        $assessed = $evaluations->where('assessed', true);
        $targetEvaluable = $evaluations->filter(fn (array $evaluation): bool => $evaluation['target_reached'] !== null);

        return [
            'key' => $level,
            'selected' => $evaluations->count(),
            'assessed' => $assessed->count(),
            'not_evaluable' => $evaluations->count() - $assessed->count(),
            'target_evaluable' => $targetEvaluable->count(),
            'target_reached' => $targetEvaluable->where('target_reached', true)->count(),
            'below_target' => $targetEvaluable->where('target_reached', false)->count(),
            'change_average' => $this->average($assessed, 'change'),
            'apc_remaining_average' => $this->average($assessed, 'apc_remaining'),
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function emptyLevels(): array
    {
        return collect(self::LEVELS)
            ->mapWithKeys(fn (string $level): array => [$level => $this->summarizeLevel($level, collect())])
            ->all();
    }

    /**
     * @param  Collection<int, object>  $facts
     */
    private function baselineFact(Collection $facts): ?object
    {
        return $this->sortBaselineFactsDesc(
            $facts->filter(fn (object $fact): bool => $this->isBaselineFact($fact)),
        )->first();
    }

    /**
     * @param  Collection<int, object>  $facts
     */
    private function currentFact(Collection $facts): ?object
    {
        return $this->sortFactsDesc(
            $facts->filter(fn (object $fact): bool => (int) $fact->end_period > self::BASELINE_YEAR),
        )->first();
    }

    private function isBaselineFact(object $fact): bool
    {
        return (int) $fact->end_period === self::BASELINE_YEAR
            || ((int) $fact->start_period <= self::BASELINE_YEAR && (int) $fact->end_period >= self::BASELINE_YEAR)
            || str_contains((string) $fact->period, (string) self::BASELINE_YEAR);
    }

    /**
     * @param  Collection<int, object>  $facts
     * @return Collection<int, object>
     */
    private function sortFactsDesc(Collection $facts): Collection
    {
        return $facts->sort(fn (object $left, object $right): int => $this->factSortValues($right) <=> $this->factSortValues($left));
    }

    /**
     * @param  Collection<int, object>  $facts
     * @return Collection<int, object>
     */
    private function sortBaselineFactsDesc(Collection $facts): Collection
    {
        return $facts->sort(fn (object $left, object $right): int => $this->baselineFactSortValues($right) <=> $this->baselineFactSortValues($left));
    }

    /**
     * @return array<int, int>
     */
    private function baselineFactSortValues(object $fact): array
    {
        return [
            $this->baselinePeriodPriority($fact),
            $this->dataSourcePriority($fact),
            -1 * $this->baselinePeriodSpan($fact),
            strtotime((string) $fact->date_lastupdated) ?: 0,
            $fact->source === 'active' ? 1 : 0,
            (int) $fact->fact_id,
        ];
    }

    /**
     * @return array<int, int>
     */
    private function factSortValues(object $fact): array
    {
        return [
            $this->dataSourcePriority($fact),
            (int) $fact->end_period,
            strtotime((string) $fact->date_lastupdated) ?: 0,
            $fact->source === 'active' ? 1 : 0,
            (int) $fact->fact_id,
        ];
    }

    private function baselinePeriodPriority(object $fact): int
    {
        $start = (int) $fact->start_period;
        $end = (int) $fact->end_period;
        $period = trim((string) $fact->period);

        if (($start === self::BASELINE_YEAR && $end === self::BASELINE_YEAR) || $period === (string) self::BASELINE_YEAR) {
            return 4;
        }

        if ($start === self::BASELINE_YEAR || preg_match('/^'.self::BASELINE_YEAR.'\s*[-\/–—]/', $period) === 1) {
            return 3;
        }

        if ($start < self::BASELINE_YEAR && $end > self::BASELINE_YEAR) {
            return 2;
        }

        return str_contains($period, (string) self::BASELINE_YEAR) ? 1 : 0;
    }

    private function baselinePeriodSpan(object $fact): int
    {
        $start = (int) $fact->start_period;
        $end = (int) $fact->end_period;

        return max(0, $end - $start);
    }

    private function dataSourcePriority(object $fact): int
    {
        return $this->dataSourceCategory($fact) === 'local' ? 1 : 0;
    }

    private function dataSourceCategory(object $fact): string
    {
        $level = mb_strtolower(trim((string) ($fact->datasource_level ?? '')));
        $label = mb_strtolower(trim(implode(' ', array_filter([
            $fact->datasource_name ?? null,
            $fact->datasource_code ?? null,
        ]))));

        if (str_contains($level, 'national') || str_contains($level, 'local') || str_contains($level, 'country')) {
            return 'local';
        }

        if (
            str_contains($label, 'country-level')
            || str_contains($label, 'national')
            || str_contains($label, 'ministry')
            || str_contains($label, 'dhis2')
            || str_contains($label, 'health information system')
        ) {
            return 'local';
        }

        if ($level !== '' || $label !== '') {
            return 'international';
        }

        return 'unknown';
    }

    private function levelFor(object $selection): string
    {
        return match ((int) $selection->group_id) {
            1 => 'day',
            2 => 'hour',
            3 => 'minute',
            4 => 'second',
            default => match (strtolower((string) $selection->indicator_type)) {
                'impact' => 'day',
                'outcome' => 'hour',
                'output' => 'minute',
                'input' => 'second',
                default => 'second',
            },
        };
    }

    private function changeMethodFor(string $level, ?string $indicatorCode): string
    {
        if ($level === 'minute') {
            return 'difference';
        }

        if ($level === 'hour' && $indicatorCode !== 'AFR0128') {
            return 'difference';
        }

        return 'percent';
    }

    private function lowerIsBetter(object $selection): bool
    {
        $code = (string) $selection->indicator_code;

        if (in_array($code, self::LOWER_IS_BETTER_CODES, true)) {
            return true;
        }

        $name = mb_strtolower((string) ($selection->indicator_name_en ?? $selection->indicator_name));

        if (str_contains($name, 'non-raised') || str_contains($name, 'do not currently use')) {
            return false;
        }

        foreach (['mortality', 'incidence', 'infection', 'expenditure', 'out of pocket', 'tobacco use', 'alcohol', 'poisoning', 'out of stock'] as $term) {
            if (str_contains($name, $term)) {
                return true;
            }
        }

        return false;
    }

    private function change(float $baseline, float $current, bool $lowerIsBetter, string $method): float
    {
        $difference = $lowerIsBetter
            ? $baseline - $current
            : $current - $baseline;

        if ($method === 'difference') {
            return $difference;
        }

        return ($difference / abs($baseline)) * 100;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $evaluations
     */
    private function average(Collection $evaluations, string $key): ?float
    {
        $values = $evaluations
            ->pluck($key)
            ->filter(fn (mixed $value): bool => $value !== null);

        return $values->isEmpty() ? null : (float) $values->average();
    }

    private function factKey(int $locationId, int $indicatorId): string
    {
        return "{$locationId}:{$indicatorId}";
    }
}
