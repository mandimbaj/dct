<?php

namespace App\Filament\Widgets;

use App\Models\Country;
use App\Models\HealthIndicatorValue;
use App\Models\Indicator;
use App\Support\DashboardCache;
use App\Support\UserCountryAccess;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;
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
        return 'bar';
    }

    protected function getData(): array
    {
        return DashboardCache::remember('top-indicators-african-region', function (): array {
            $africanRegionLocationIds = self::africanRegionLocationIds();

            if (UserCountryAccess::canViewRegionalDashboard() && $africanRegionLocationIds === []) {
                return [
                    'datasets' => [[
                        'label' => __('aho.charts.records'),
                        'data' => [],
                        'backgroundColor' => '#009edb',
                    ]],
                    'labels' => [],
                ];
            }

            $query = HealthIndicatorValue::query()
                ->select('indicator_id', DB::raw('count(*) as total'))
                ->whereNotNull('indicator_id')
                ->groupBy('indicator_id')
                ->orderByDesc('total')
                ->limit(10);

            if (UserCountryAccess::canViewRegionalDashboard()) {
                $query->whereIn('location_id', $africanRegionLocationIds);
            } else {
                UserCountryAccess::scopeDashboard($query);
            }

            $rows = $query->get();
            $indicators = Indicator::with('translations')
                ->whereIn('indicator_id', $rows->pluck('indicator_id'))
                ->get()
                ->keyBy('indicator_id');

            return [
                'datasets' => [[
                    'label' => __('aho.charts.records'),
                    'data' => $rows->pluck('total')->map(fn ($value): int => (int) $value)->all(),
                    'backgroundColor' => '#009a61',
                ]],
                'labels' => $rows
                    ->map(fn ($row): string => $indicators->get($row->indicator_id)?->display_name ?? (string) $row->indicator_id)
                    ->all(),
            ];
        }, 15);
    }

    public function getHeading(): string
    {
        return __('aho.charts.top_indicators_african_region');
    }

    /**
     * @return array<int, int>
     */
    private static function africanRegionLocationIds(): array
    {
        return Country::query()
            ->where(function (Builder $query): void {
                $query
                    ->whereIn('code', ['AFRO', 'AFR', 'AFRICA', 'AFRICAN_REGION'])
                    ->orWhereIn('iso_alpha', ['AF', 'AFR', 'AFRO'])
                    ->orWhereHas('translations', function (Builder $query): void {
                        $query
                            ->where('name', 'like', '%African Region%')
                            ->orWhere('name', 'like', '%Africa Region%')
                            ->orWhere('name', 'like', '%Région africaine%')
                            ->orWhere('name', 'like', '%Region africaine%')
                            ->orWhere('name', 'like', '%Região Africana%')
                            ->orWhere('name', 'like', '%AFRO%');
                    });
            })
            ->pluck('location_id')
            ->map(fn ($locationId): int => (int) $locationId)
            ->all();
    }
}
