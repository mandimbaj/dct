<?php

namespace App\Filament\Widgets;

use App\Models\Indicator;
use App\Support\DashboardCache;
use App\Support\DashboardIndicatorValues;
use Filament\Widgets\ChartWidget;

class DataQualitySummaryChart extends ChartWidget
{
    protected static bool $isDiscovered = true;

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
        return 'doughnut';
    }

    protected function getData(): array
    {
        return DashboardCache::remember('top-indicators-by-country-use-light.v2', function (): array {
            $rows = DashboardIndicatorValues::indicatorCountryUseWithArchiveFallback()->take(5);
            $indicators = Indicator::with('translations')
                ->whereIn('indicator_id', $rows->pluck('indicator_id'))
                ->get()
                ->keyBy('indicator_id');

            return [
                'datasets' => [[
                    'label' => __('aho.charts.countries'),
                    'data' => $rows->pluck('countries_count')->map(fn ($value): int => (int) $value)->all(),
                    'backgroundColor' => ['#0072a0', '#009edb', '#009a61', '#f5a623', '#6b7280'],
                ]],
                'labels' => $rows
                    ->map(fn ($row): string => $indicators->get($row->indicator_id)?->display_name ?? (string) $row->indicator_id)
                    ->all(),
            ];
        });
    }

    public function getHeading(): string
    {
        return __('aho.charts.top_indicators_by_country_use');
    }
}
