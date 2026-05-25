<?php

namespace App\Filament\Widgets;

use App\Models\Country;
use App\Models\KnowledgeProduct;
use App\Support\DashboardCache;
use App\Support\DashboardIndicatorValues;
use App\Support\UserCountryAccess;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RegionalValuesByCountryChart extends ChartWidget
{
    protected static bool $isDiscovered = true;

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
        return 'doughnut';
    }

    protected function getData(): array
    {
        return DashboardCache::remember('recent-uploads-by-country-light', function (): array {
            $indicatorUploads = DashboardIndicatorValues::currentRecentUploadsByLocation();

            $publicationQuery = KnowledgeProduct::query()
                ->select(
                    'location_id',
                    DB::raw('max(coalesce(date_lastupdated, date_created)) as latest_at'),
                    DB::raw('count(*) as total')
                )
                ->whereNotNull('location_id')
                ->groupBy('location_id');

            UserCountryAccess::scopeDashboard($publicationQuery);

            $publicationUploads = $publicationQuery->get();

            $uploads = $indicatorUploads->concat($publicationUploads);
            $uploadLocations = Country::with([
                'parent.translations',
                'parent.parent.translations',
                'parent.parent.parent.translations',
                'translations',
            ])
                ->whereIn('location_id', $uploads->pluck('location_id'))
                ->get()
                ->keyBy('location_id');

            $rows = $uploads
                ->map(fn ($item): object => (object) [
                    'country_id' => self::countryIdForLocation($uploadLocations->get($item->location_id), (int) $item->location_id),
                    'latest_at' => $item->latest_at,
                    'total' => (int) $item->total,
                ])
                ->groupBy('country_id')
                ->map(fn ($items, $countryId): object => (object) [
                    'country_id' => (int) $countryId,
                    'latest_at' => $items->max('latest_at'),
                    'total' => (int) $items->sum('total'),
                ])
                ->filter(fn (object $row): bool => filled($row->latest_at))
                ->sortByDesc('latest_at')
                ->take(5)
                ->values();

            $countries = Country::with('translations')
                ->whereIn('location_id', $rows->pluck('country_id'))
                ->get()
                ->keyBy('location_id');

            return [
                'datasets' => [[
                    'label' => __('aho.charts.records'),
                    'data' => $rows->pluck('total')->map(fn ($value): int => (int) $value)->all(),
                    'backgroundColor' => ['#009edb', '#0072a0', '#009a61', '#f5a623', '#6b7280'],
                ]],
                'labels' => $rows
                    ->map(function (object $row) use ($countries): string {
                        $country = $countries->get($row->country_id)?->display_name ?? (string) $row->country_id;
                        $date = Carbon::parse($row->latest_at)->format('Y-m-d');

                        return "{$country} ({$date})";
                    })
                    ->all(),
            ];
        });
    }

    public function getHeading(): string
    {
        return __('aho.charts.recent_uploads_by_country');
    }

    private static function countryIdForLocation(?Country $location, int $fallback): int
    {
        $candidate = $location;
        $depth = 0;

        while ($candidate?->parent && (int) $candidate->locationlevel_id > 2 && $depth < 5) {
            $candidate = $candidate->parent;
            $depth++;
        }

        return (int) ($candidate?->location_id ?? $fallback);
    }
}
