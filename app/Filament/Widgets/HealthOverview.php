<?php

namespace App\Filament\Widgets;

use App\Models\Country;
use App\Models\DataSource;
use App\Models\Indicator;
use App\Models\MeasureMethod;
use App\Models\User;
use App\Support\ApprovalWorkflow;
use App\Support\DashboardCache;
use App\Support\DashboardIndicatorValues;
use App\Support\UserCountryAccess;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class HealthOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $stats = DashboardCache::remember('health-overview', function (): array {
            $locations = Country::query();
            $levelTwoLocations = Country::query()
                ->whereHas('parent', fn ($query) => $query->where('locationlevel_id', 2));

            UserCountryAccess::scopeDashboard($locations, 'location_id');

            if (! UserCountryAccess::canViewRegionalDashboard()) {
                $levelTwoLocations->where('parent_id', UserCountryAccess::locationId());
            }

            $currentIndicatorTotal = DashboardIndicatorValues::currentCount();
            $archivedIndicatorTotal = DashboardIndicatorValues::archivedCount();
            $statusCounts = DashboardIndicatorValues::currentStatusCounts();

            // Count users: super admins see all users, others see only users of their assigned country
            if (UserCountryAccess::canViewAllCountries()) {
                $usersCount = User::count();
            } else {
                $usersCount = User::where('location_id', UserCountryAccess::locationId())->count();
            }

            return [
                'locations' => $locations->count(),
                'level_two_locations' => $levelTwoLocations->count(),
                'indicators' => Indicator::count(),
                'indicators_with_values' => DashboardIndicatorValues::distinctIndicatorCount(),
                'values' => $currentIndicatorTotal,
                'indicator_values' => $currentIndicatorTotal,
                'indicator_current_values' => $currentIndicatorTotal,
                'indicator_archive_values' => $archivedIndicatorTotal,
                'approved_values' => (int) ($statusCounts[ApprovalWorkflow::STATUS_APPROVED] ?? 0),
                'pending_values' => (int) ($statusCounts[ApprovalWorkflow::STATUS_PENDING] ?? 0),
                'rejected_values' => (int) ($statusCounts[ApprovalWorkflow::STATUS_REJECTED] ?? 0),
                'sources' => DataSource::count(),
                'methods' => MeasureMethod::count(),
                'users' => $usersCount,
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
            Stat::make(__('aho.stats.archive_values'), $stats['indicator_archive_values'])
                ->description(__('aho.stats.archive_values_description'))
                ->icon('heroicon-o-archive-box'),
            Stat::make(__('aho.stats.sources_methods'), $stats['sources'].' / '.$stats['methods'])
                ->description(__('aho.stats.sources_methods_description'))
                ->icon('heroicon-o-circle-stack'),
            Stat::make(__('aho.stats.users'), $stats['users'])
                ->description(__('aho.stats.users_description'))
                ->icon('heroicon-o-users'),
        ];
    }
}
