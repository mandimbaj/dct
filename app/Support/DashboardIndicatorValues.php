<?php

namespace App\Support;

use Closure;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardIndicatorValues
{
    private const LIVE_TABLE = 'fact_data_indicators';

    private const ARCHIVE_TABLE = 'fact_data_archive';

    public static function currentCount(): int
    {
        return self::countForTable(self::LIVE_TABLE);
    }

    public static function archivedCount(): int
    {
        if (! self::hasArchiveTable()) {
            return 0;
        }

        return self::countForTable(self::ARCHIVE_TABLE);
    }

    public static function totalCount(): int
    {
        return self::currentCount() + self::archivedCount();
    }

    public static function currentStatusCounts(): Collection
    {
        return self::statusCountsForTable(self::LIVE_TABLE);
    }

    public static function statusCounts(): Collection
    {
        return self::currentStatusCounts();
    }

    public static function distinctIndicatorCount(): int
    {
        return collect(self::tables())
            ->flatMap(fn (string $table): Collection => self::scopedTable($table)
                ->whereNotNull("{$table}.indicator_id")
                ->distinct()
                ->pluck("{$table}.indicator_id"))
            ->unique()
            ->count();
    }

    public static function groupedCounts(string $column, ?Closure $callback = null): Collection
    {
        return self::groupedCountsForTables(self::tables(), $column, $callback);
    }

    public static function currentGroupedCounts(string $column, ?Closure $callback = null): Collection
    {
        return self::groupedCountsForTables([self::LIVE_TABLE], $column, $callback);
    }

    public static function groupedCountsWithArchiveFallback(string $column, ?Closure $callback = null): Collection
    {
        // Active and archived rows are two lifecycle partitions of the same
        // indicator dataset. A single active row must not hide a country's
        // historical values from dashboard rankings.
        if (! UserCountryAccess::canViewRegionalDashboard()) {
            return self::groupedCounts($column, $callback);
        }

        $rows = self::currentGroupedCounts($column, $callback);

        return $rows->isNotEmpty()
            ? $rows
            : self::groupedCounts($column, $callback);
    }

    public static function recentUploadsByLocation(): Collection
    {
        return self::recentUploadsByLocationForTables(self::tables());
    }

    public static function currentRecentUploadsByLocation(): Collection
    {
        return self::recentUploadsByLocationForTables([self::LIVE_TABLE]);
    }

    public static function archivedRecentUploadsByLocation(int $limit = 5000): Collection
    {
        if (! self::hasArchiveTable()) {
            return collect();
        }

        $table = self::ARCHIVE_TABLE;
        $recentUploads = self::scopedTable($table)
            ->select([
                "{$table}.location_id",
                "{$table}.date_created",
                "{$table}.date_lastupdated",
            ])
            ->whereNotNull("{$table}.location_id")
            ->whereRaw("coalesce({$table}.date_lastupdated, {$table}.date_created) is not null")
            ->orderByDesc("{$table}.fact_id")
            ->limit($limit);

        return DB::connection('warehouse')
            ->query()
            ->fromSub($recentUploads, 'recent_archive_uploads')
            ->select(
                'location_id',
                DB::raw('max(coalesce(date_lastupdated, date_created)) as latest_at'),
                DB::raw('count(*) as total')
            )
            ->groupBy('location_id')
            ->get()
            ->map(fn (object $row): object => (object) [
                'location_id' => (int) $row->location_id,
                'latest_at' => $row->latest_at,
                'total' => (int) $row->total,
            ]);
    }

    public static function currentRecentUploadsByLocationWithArchiveFallback(): Collection
    {
        $uploads = self::currentRecentUploadsByLocation()
            ->filter(fn (object $row): bool => filled($row->latest_at))
            ->values();

        return $uploads->isNotEmpty()
            ? $uploads
            : self::archivedRecentUploadsByLocation();
    }

    public static function indicatorCountryUse(): Collection
    {
        return self::indicatorCountryUseForTables(self::tables());
    }

    public static function currentIndicatorCountryUse(): Collection
    {
        return self::indicatorCountryUseForTables([self::LIVE_TABLE]);
    }

    public static function indicatorCountryUseWithArchiveFallback(): Collection
    {
        if (! UserCountryAccess::canViewRegionalDashboard()) {
            return self::indicatorCountryUse();
        }

        $rows = self::currentIndicatorCountryUse();

        return $rows->isNotEmpty()
            ? $rows
            : self::indicatorCountryUse();
    }

    /**
     * @param  array<int, string>  $tables
     */
    private static function groupedCountsForTables(array $tables, string $column, ?Closure $callback = null): Collection
    {
        return collect($tables)
            ->flatMap(function (string $table) use ($column, $callback): Collection {
                $query = self::withoutArchivedDuplicates(self::scopedTable($table), $table)
                    ->select("{$table}.{$column}", DB::raw('count(*) as total'))
                    ->whereNotNull("{$table}.{$column}")
                    ->groupBy("{$table}.{$column}");

                $callback?->__invoke($query, $table);

                return $query->get()
                    ->map(fn (object $row): object => (object) [
                        $column => $row->{$column},
                        'total' => (int) $row->total,
                    ]);
            })
            ->groupBy($column)
            ->map(fn (Collection $rows, mixed $id): object => (object) [
                $column => $id,
                'total' => (int) $rows->sum('total'),
            ])
            ->sortByDesc('total')
            ->values();
    }

    /**
     * @param  array<int, string>  $tables
     */
    private static function recentUploadsByLocationForTables(array $tables): Collection
    {
        return collect($tables)
            ->flatMap(fn (string $table): Collection => self::scopedTable($table)
                ->select(
                    "{$table}.location_id",
                    DB::raw("max(coalesce({$table}.date_lastupdated, {$table}.date_created)) as latest_at"),
                    DB::raw('count(*) as total')
                )
                ->whereNotNull("{$table}.location_id")
                ->groupBy("{$table}.location_id")
                ->get())
            ->groupBy('location_id')
            ->map(fn (Collection $rows, mixed $locationId): object => (object) [
                'location_id' => (int) $locationId,
                'latest_at' => $rows->max('latest_at'),
                'total' => (int) $rows->sum('total'),
            ])
            ->values();
    }

    /**
     * @param  array<int, string>  $tables
     */
    private static function indicatorCountryUseForTables(array $tables): Collection
    {
        return collect($tables)
            ->flatMap(function (string $table): Collection {
                $query = self::withoutArchivedDuplicates(self::scopedTable($table), $table)
                    ->leftJoin('stg_location as value_locations', 'value_locations.location_id', '=', "{$table}.location_id")
                    ->select(
                        "{$table}.indicator_id",
                        DB::raw("case when value_locations.locationlevel_id > 2 and value_locations.parent_id is not null then value_locations.parent_id else {$table}.location_id end as country_id")
                    )
                    ->whereNotNull("{$table}.indicator_id")
                    ->whereNotNull("{$table}.location_id")
                    ->groupBy("{$table}.indicator_id", 'country_id');

                return $query->get();
            })
            ->groupBy('indicator_id')
            ->map(fn (Collection $rows, mixed $indicatorId): object => (object) [
                'indicator_id' => $indicatorId,
                'countries_count' => $rows
                    ->pluck('country_id')
                    ->filter()
                    ->unique()
                    ->count(),
            ])
            ->sortByDesc('countries_count')
            ->take(10)
            ->values();
    }

    /**
     * @return array<int, string>
     */
    private static function tables(): array
    {
        return self::hasArchiveTable()
            ? [self::LIVE_TABLE, self::ARCHIVE_TABLE]
            : [self::LIVE_TABLE];
    }

    private static function countForTable(string $table): int
    {
        return (int) self::scopedTable($table)->count();
    }

    private static function statusCountsForTable(string $table): Collection
    {
        $column = self::statusColumn($table);
        $statusExpression = "case when lower(trim({$table}.{$column})) in ('approved', 'pending', 'rejected') ".
            "then lower(trim({$table}.{$column})) else 'pending' end";

        return self::scopedTable($table)
            ->selectRaw("{$statusExpression} as status")
            ->selectRaw('count(*) as total')
            ->groupByRaw($statusExpression)
            ->get()
            ->mapWithKeys(fn (object $row): array => [
                $row->status => (int) $row->total,
            ]);
    }

    private static function scopedTable(string $table): QueryBuilder
    {
        return self::scopeDashboard(DB::connection('warehouse')->table($table), $table);
    }

    private static function withoutArchivedDuplicates(QueryBuilder $query, string $table): QueryBuilder
    {
        if (
            $table !== self::ARCHIVE_TABLE
            || ! Schema::connection('warehouse')->hasColumn(self::LIVE_TABLE, 'uuid')
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

    private static function scopeDashboard(QueryBuilder $query, string $table): QueryBuilder
    {
        if (UserCountryAccess::canViewRegionalDashboard()) {
            return $query;
        }

        $locationIds = UserCountryAccess::allowedLocationIds();

        return $locationIds === []
            ? $query->whereRaw('1 = 0')
            : $query->whereIn("{$table}.location_id", $locationIds);
    }

    private static function hasArchiveTable(): bool
    {
        return Schema::connection('warehouse')->hasTable(self::ARCHIVE_TABLE);
    }

    private static function statusColumn(string $table): string
    {
        return Schema::connection('warehouse')->hasColumn($table, ApprovalWorkflow::STATUS_COLUMN)
            ? ApprovalWorkflow::STATUS_COLUMN
            : ApprovalWorkflow::MIRROR_COLUMN;
    }
}
