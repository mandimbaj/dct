<?php

namespace App\Filament\Widgets;

use App\Models\HealthIndicatorValue;
use App\Models\Indicator;
use App\Support\DashboardCache;
use App\Support\UserCountryAccess;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class DataQualitySummaryChart extends ChartWidget
{
    protected ?string $heading = null;

    protected static ?int $sort = 22;

    protected static bool $isLazy = true;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        return DashboardCache::remember('top-indicators-by-country-use', function (): array {
            $query = HealthIndicatorValue::query()
                ->leftJoin('stg_location as value_locations', 'value_locations.location_id', '=', 'fact_data_indicators.location_id')
                ->select(
                    'fact_data_indicators.indicator_id',
                    DB::raw('count(distinct case when value_locations.locationlevel_id > 2 and value_locations.parent_id is not null then value_locations.parent_id else fact_data_indicators.location_id end) as countries_count')
                )
                ->whereNotNull('fact_data_indicators.indicator_id')
                ->whereNotNull('fact_data_indicators.location_id')
                ->groupBy('fact_data_indicators.indicator_id')
                ->orderByDesc('countries_count')
                ->limit(10);

            UserCountryAccess::scopeDashboard($query);

            $rows = $query->get();
            $indicators = Indicator::with('translations')
                ->whereIn('indicator_id', $rows->pluck('indicator_id'))
                ->get()
                ->keyBy('indicator_id');

            return [
                'datasets' => [[
                    'label' => __('aho.charts.countries'),
                    'data' => $rows->pluck('countries_count')->map(fn ($value): int => (int) $value)->all(),
                    'backgroundColor' => '#0072a0',
                ]],
                'labels' => $rows
                    ->map(fn ($row): string => $indicators->get($row->indicator_id)?->display_name ?? (string) $row->indicator_id)
                    ->all(),
            ];
        }, 15);
    }

    public function getHeading(): string
    {
        return __('aho.charts.top_indicators_by_country_use');
    }
}
