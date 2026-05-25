<?php

namespace App\Filament\Widgets;

use App\Models\DataSource;
use App\Support\DashboardCache;
use App\Support\DashboardIndicatorValues;
use Filament\Widgets\ChartWidget;

class RegionalValuesByDataSourceChart extends ChartWidget
{
    protected static bool $isDiscovered = true;

    protected ?string $heading = null;

    protected static ?int $sort = 23;

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
        return DashboardCache::remember('top-data-sources-light', function (): array {
            $rows = DashboardIndicatorValues::currentGroupedCounts('datasource_id')->take(5);
            $sources = DataSource::with('translations')
                ->whereIn('datasource_id', $rows->pluck('datasource_id'))
                ->get()
                ->keyBy('datasource_id');

            return [
                'datasets' => [[
                    'label' => __('aho.charts.records'),
                    'data' => $rows->pluck('total')->map(fn ($value): int => (int) $value)->all(),
                    'backgroundColor' => ['#009edb', '#0072a0', '#009a61', '#f5a623', '#6b7280'],
                ]],
                'labels' => $rows
                    ->map(fn ($row): string => $sources->get($row->datasource_id)?->display_name ?? (string) $row->datasource_id)
                    ->all(),
            ];
        });
    }

    public function getHeading(): string
    {
        return __('aho.charts.top_data_sources');
    }
}
