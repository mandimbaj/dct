<?php

namespace App\Filament\Widgets;

use App\Models\DataSource;
use App\Models\HealthIndicatorValue;
use App\Support\ApprovalWorkflow;
use App\Support\DashboardCache;
use App\Support\MortalityIndicators;
use App\Support\UserCountryAccess;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class RegionalValuesByDataSourceChart extends ChartWidget
{
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
        return 'bar';
    }

    protected function getData(): array
    {
        return DashboardCache::remember('mortality-by-data-source', function (): array {
            $query = HealthIndicatorValue::query()
                ->select('datasource_id', DB::raw('count(*) as total'))
                ->whereNotNull('datasource_id')
                ->where(ApprovalWorkflow::STATUS_COLUMN, ApprovalWorkflow::STATUS_APPROVED)
                ->groupBy('datasource_id')
                ->orderByDesc('total')
                ->limit(8);

            MortalityIndicators::scopeValues($query);
            UserCountryAccess::scopeDashboard($query);

            $rows = $query->get();
            $sources = DataSource::with('translations')
                ->whereIn('datasource_id', $rows->pluck('datasource_id'))
                ->get()
                ->keyBy('datasource_id');

            return [
                'datasets' => [[
                    'label' => __('aho.charts.records'),
                    'data' => $rows->pluck('total')->map(fn ($value): int => (int) $value)->all(),
                    'backgroundColor' => ['#009edb', '#0072a0', '#009a61', '#6aa84f', '#f5a623', '#6b7280', '#00a3a1', '#8dc63f'],
                ]],
                'labels' => $rows
                    ->map(fn ($row): string => $sources->get($row->datasource_id)?->display_name ?? (string) $row->datasource_id)
                    ->all(),
            ];
        }, 15);
    }

    public function getHeading(): string
    {
        return __('aho.charts.mortality_by_data_source');
    }
}
