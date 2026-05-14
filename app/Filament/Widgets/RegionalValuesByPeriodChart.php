<?php

namespace App\Filament\Widgets;

use App\Models\HealthIndicatorValue;
use App\Support\ApprovalWorkflow;
use App\Support\DashboardCache;
use App\Support\MortalityIndicators;
use App\Support\UserCountryAccess;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class RegionalValuesByPeriodChart extends ChartWidget
{
    protected ?string $heading = null;

    protected static ?int $sort = 21;

    protected static bool $isLazy = true;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        return DashboardCache::remember('mortality-by-period', function (): array {
            $query = HealthIndicatorValue::query()
                ->select('period', DB::raw('round(avg(value_received), 2) as average_value'))
                ->whereNotNull('value_received')
                ->where(ApprovalWorkflow::STATUS_COLUMN, ApprovalWorkflow::STATUS_APPROVED)
                ->groupBy('period')
                ->orderByDesc('period')
                ->limit(8);

            MortalityIndicators::scopeValues($query);
            UserCountryAccess::scopeDashboard($query);

            $rows = $query->get()->reverse()->values();

            return [
                'datasets' => [[
                    'label' => __('aho.charts.average_value'),
                    'data' => $rows->pluck('average_value')->map(fn ($value): float => (float) $value)->all(),
                    'borderColor' => '#009edb',
                    'backgroundColor' => 'rgba(0, 158, 219, 0.16)',
                    'fill' => true,
                    'tension' => 0.35,
                ]],
                'labels' => $rows
                    ->map(fn ($row): string => (string) $row->period)
                    ->all(),
            ];
        }, 15);
    }

    public function getHeading(): string
    {
        return __('aho.charts.mortality_by_period');
    }
}
