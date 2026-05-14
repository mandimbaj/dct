<?php

namespace App\Filament\Widgets;

use App\Models\Country;
use App\Models\HealthIndicatorValue;
use App\Support\ApprovalWorkflow;
use App\Support\DashboardCache;
use App\Support\MortalityIndicators;
use App\Support\UserCountryAccess;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class RegionalValuesByCountryChart extends ChartWidget
{
    protected ?string $heading = null;

    protected static ?int $sort = 20;

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
        return DashboardCache::remember('mortality-by-country', function (): array {
            $query = HealthIndicatorValue::query()
                ->select('location_id', DB::raw('round(avg(value_received), 2) as average_value'))
                ->whereNotNull('value_received')
                ->where(ApprovalWorkflow::STATUS_COLUMN, ApprovalWorkflow::STATUS_APPROVED)
                ->groupBy('location_id')
                ->orderByDesc('average_value')
                ->limit(8);

            MortalityIndicators::scopeValues($query);
            UserCountryAccess::scopeDashboard($query);

            $rows = $query->get();
            $locations = Country::with('translations')
                ->whereIn('location_id', $rows->pluck('location_id'))
                ->get()
                ->keyBy('location_id');

            return [
                'datasets' => [[
                    'label' => __('aho.charts.average_value'),
                    'data' => $rows->pluck('average_value')->map(fn ($value): float => (float) $value)->all(),
                    'backgroundColor' => '#009edb',
                ]],
                'labels' => $rows
                    ->map(fn ($row): string => $locations->get($row->location_id)?->display_name ?? (string) $row->location_id)
                    ->all(),
            ];
        }, 15);
    }

    public function getHeading(): string
    {
        return __('aho.charts.mortality_by_country');
    }
}
