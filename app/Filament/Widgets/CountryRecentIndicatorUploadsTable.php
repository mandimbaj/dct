<?php

namespace App\Filament\Widgets;

use App\Models\Country;
use App\Models\HealthIndicatorArchive;
use App\Models\HealthIndicatorValue;
use App\Models\Indicator;
use App\Support\CountryTableFilter;
use App\Support\DashboardCache;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CountryRecentIndicatorUploadsTable extends Widget
{
    protected static bool $isDiscovered = true;

    protected static ?int $sort = 20;

    protected static bool $isLazy = true;

    protected string $view = 'filament.widgets.country-recent-indicator-uploads-table';

    protected int|string|array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    private const MAX_ROWS = 10;

    private const SOURCE_SCAN_LIMIT = 120;

    private static ?bool $hasArchiveTable = null;

    /**
     * @var array<int, string>
     */
    private const COLORS = [
        '#009edb',
        '#0072a0',
        '#009a61',
        '#f5a623',
        '#6b7280',
    ];

    public static function canView(): bool
    {
        return RegionalValuesByCountryChart::countryDashboardForRequest() instanceof Country;
    }

    protected function getViewData(): array
    {
        $country = RegionalValuesByCountryChart::countryDashboardForRequest();

        return [
            'description' => null,
            'heading' => __('aho.charts.recent_country_indicator_uploads'),
            'rows' => $country instanceof Country ? $this->recentIndicatorRows($country) : collect(),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function recentIndicatorRows(Country $country): Collection
    {
        $rows = DashboardCache::remember('recent-country-indicator-uploads-table.v4.'.$country->getKey(), function () use ($country): array {
            $locationIds = CountryTableFilter::locationAndDescendantIds((int) $country->location_id);

            if ($locationIds === []) {
                return [];
            }

            $models = [HealthIndicatorValue::class];

            if (self::hasArchiveTable()) {
                $models[] = HealthIndicatorArchive::class;
            }

            $rows = collect($models)
                ->flatMap(fn (string $model): mixed => $model::query()
                    ->select([
                        'fact_id',
                        'indicator_id',
                        'location_id',
                        'date_created',
                        'date_lastupdated',
                    ])
                    ->whereIn('location_id', $locationIds)
                    ->whereNotNull('indicator_id')
                    ->orderByRaw('coalesce(date_lastupdated, date_created) desc')
                    ->orderByDesc('fact_id')
                    ->limit(self::SOURCE_SCAN_LIMIT)
                    ->get())
                ->sortByDesc(fn (mixed $row): int => self::uploadTimestamp($row))
                ->unique('indicator_id')
                ->take(self::MAX_ROWS)
                ->values();

            $indicators = Indicator::with('translations')
                ->whereIn('indicator_id', $rows->pluck('indicator_id'))
                ->get()
                ->keyBy('indicator_id');

            $rows = $rows
                ->map(fn (mixed $row, int $index): array => [
                    'color' => self::COLORS[$index % count(self::COLORS)],
                    'indicator' => Str::limit($indicators->get($row->indicator_id)?->display_name ?? (string) $row->indicator_id, 84),
                    'rank' => $index + 1,
                    'uploaded_at' => self::uploadDateLabel($row),
                ]);

            return $rows->all();
        });

        return is_array($rows) ? collect($rows) : collect();
    }

    private static function hasArchiveTable(): bool
    {
        return self::$hasArchiveTable ??= Schema::connection('warehouse')->hasTable('fact_data_archive');
    }

    private static function uploadDateLabel(mixed $row): string
    {
        $date = $row->date_lastupdated ?? $row->date_created;

        if ($date instanceof Carbon) {
            return $date->format('Y-m-d');
        }

        return filled($date) ? Carbon::parse($date)->format('Y-m-d') : __('aho.fields.not_available');
    }

    private static function uploadTimestamp(mixed $row): int
    {
        $date = $row->date_lastupdated ?? $row->date_created;

        if ($date instanceof Carbon) {
            return $date->getTimestamp();
        }

        return filled($date) ? Carbon::parse($date)->getTimestamp() : 0;
    }
}
