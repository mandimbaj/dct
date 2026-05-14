<?php

namespace App\Filament\Widgets;

use App\Models\Country;
use App\Models\DataSource;
use App\Models\HealthIndicatorValue;
use App\Models\Indicator;
use App\Models\MeasureMethod;
use App\Support\ApprovalWorkflow;
use App\Support\DashboardCache;
use App\Support\UserCountryAccess;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class HealthOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = true;

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $stats = DashboardCache::remember('health-overview', function (): array {
            $locations = Country::query();
            $levelTwoLocations = Country::query()
                ->whereHas('parent', fn ($query) => $query->where('locationlevel_id', 2));
            $values = HealthIndicatorValue::query();

            UserCountryAccess::scopeDashboard($locations, 'location_id');
            UserCountryAccess::scopeDashboard($values);

            if (! UserCountryAccess::canViewRegionalDashboard()) {
                $levelTwoLocations->where('parent_id', UserCountryAccess::locationId());
            }

            $statusCounts = (clone $values)
                ->selectRaw(
                    "case when lower(trim(".ApprovalWorkflow::STATUS_COLUMN.")) in ('approved', 'pending', 'rejected') ".
                    'then lower(trim('.ApprovalWorkflow::STATUS_COLUMN.")) else 'pending' end as status"
                )
                ->selectRaw('count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            $totalValues = (int) $statusCounts->sum();
            $approvedValues = (int) ($statusCounts[ApprovalWorkflow::STATUS_APPROVED] ?? 0);
            $pendingValues = (int) ($statusCounts[ApprovalWorkflow::STATUS_PENDING] ?? 0);
            $rejectedValues = (int) ($statusCounts[ApprovalWorkflow::STATUS_REJECTED] ?? 0);

            return [
                'locations' => $locations->count(),
                'level_two_locations' => $levelTwoLocations->count(),
                'indicators' => Indicator::count(),
                'indicators_with_values' => (clone $values)
                    ->whereNotNull('indicator_id')
                    ->distinct('indicator_id')
                    ->count('indicator_id'),
                'values' => $totalValues,
                'approved_values' => $approvedValues,
                'pending_values' => $pendingValues,
                'rejected_values' => $rejectedValues,
                'sources' => DataSource::count(),
                'methods' => MeasureMethod::count(),
            ];
        });

        return [
            Stat::make(__('aho.stats.locations'), $stats['locations'])
                ->description(__('aho.stats.locations_description', [
                    'level2' => number_format($stats['level_two_locations']),
                ]))
                ->icon('heroicon-o-globe-europe-africa'),
            Stat::make(__('aho.stats.indicators'), $stats['indicators'])
                ->description(__('aho.stats.indicators_description', [
                    'with_values' => number_format($stats['indicators_with_values']),
                ]))
                ->icon('heroicon-o-document-chart-bar'),
            Stat::make(__('aho.stats.values'), $stats['values'])
                ->description(__('aho.stats.values_description', [
                    'approved' => number_format($stats['approved_values']),
                    'pending' => number_format($stats['pending_values']),
                    'rejected' => number_format($stats['rejected_values']),
                ]))
                ->icon('heroicon-o-chart-bar-square'),
            Stat::make(__('aho.stats.sources_methods'), $stats['sources'].' / '.$stats['methods'])
                ->description(__('aho.stats.sources_methods_description'))
                ->icon('heroicon-o-circle-stack'),
        ];
    }
}
