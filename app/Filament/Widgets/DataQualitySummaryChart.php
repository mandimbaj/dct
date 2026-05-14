<?php

namespace App\Filament\Widgets;

use App\Models\HealthIndicatorValue;
use App\Models\Indicator;
use App\Support\ApprovalWorkflow;
use App\Support\DashboardCache;
use App\Support\MortalityIndicators;
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
        return 'doughnut';
    }

    protected function getData(): array
    {
        return DashboardCache::remember('mortality-records-by-indicator', function (): array {
            $query = HealthIndicatorValue::query()
                ->select('indicator_id', DB::raw('count(*) as total'))
                ->where(ApprovalWorkflow::STATUS_COLUMN, ApprovalWorkflow::STATUS_APPROVED)
                ->groupBy('indicator_id')
                ->orderByDesc('total')
                ->limit(6);

            MortalityIndicators::scopeValues($query);
            UserCountryAccess::scopeDashboard($query);

            $rows = $query->get();
            $indicators = Indicator::with('translations')
                ->whereIn('indicator_id', $rows->pluck('indicator_id'))
                ->get()
                ->keyBy('indicator_id');

            return [
                'datasets' => [[
                    'data' => $rows->pluck('total')->all(),
                    'backgroundColor' => ['#009edb', '#0072a0', '#009a61', '#6aa84f', '#f5a623', '#6b7280'],
                ]],
                'labels' => $rows
                    ->map(fn ($row): string => $indicators->get($row->indicator_id)?->display_name ?? (string) $row->indicator_id)
                    ->all(),
            ];
        }, 15);
    }

    public function getHeading(): string
    {
        return __('aho.charts.mortality_records_by_indicator');
    }
}
