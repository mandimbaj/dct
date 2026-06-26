<?php

namespace App\Filament\Widgets;

use App\Models\Country;
use App\Models\HealthIndicatorArchive;
use App\Models\HealthIndicatorValue;
use App\Models\KnowledgeProduct;
use App\Support\CountryTableFilter;
use App\Support\DashboardCache;
use App\Support\DashboardIndicatorValues;
use App\Support\UserCountryAccess;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

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

    public static function canView(): bool
    {
        return ! (self::countryDashboardForRequest() instanceof Country);
    }

    public static function countryDashboardForRequest(): ?Country
    {
        $countryCode = strtolower(trim((string) (request()->route('country') ?: request()->segment(2))));

        if ($countryCode !== '' && ! in_array($countryCode, ['af', 'global'], true)) {
            $country = Country::with('translations')
                ->where('locationlevel_id', 2)
                ->where(function ($query) use ($countryCode): void {
                    $query
                        ->whereRaw('lower(iso_alpha) like ?', [$countryCode.'%'])
                        ->orWhereRaw('lower(code) = ?', [$countryCode]);
                })
                ->first();

            if ($country instanceof Country) {
                return $country;
            }
        }

        if (UserCountryAccess::canViewRegionalDashboard() || blank(UserCountryAccess::locationId())) {
            return null;
        }

        return Country::query()
            ->with('translations')
            ->where('locationlevel_id', 2)
            ->whereKey(UserCountryAccess::locationId())
            ->first();
    }

    protected function getType(): string
    {
        return $this->countryDashboard() instanceof Country ? 'bar' : 'doughnut';
    }

    protected function getData(): array
    {
        if ($country = $this->countryDashboard()) {
            return $this->getCountryRecentIndicatorUploadsData($country);
        }

        return DashboardCache::remember('recent-uploads-by-country-light.v4', function (): array {
            $publicationUploads = $this->regionalPublicationUploads();
            $currentIndicatorUploads = DashboardIndicatorValues::currentRecentUploadsByLocation()
                ->filter(fn (object $row): bool => filled($row->latest_at))
                ->values();
            $usingArchivedIndicators = $currentIndicatorUploads->isEmpty();
            $indicatorUploads = $usingArchivedIndicators
                ? DashboardIndicatorValues::archivedRecentUploadsByLocation()
                : $currentIndicatorUploads;

            $rows = $this->regionalRecentUploadRows(
                $indicatorUploads,
                $publicationUploads,
            );

            if ($rows->isEmpty() && ! $usingArchivedIndicators) {
                $rows = $this->regionalRecentUploadRows(
                    DashboardIndicatorValues::archivedRecentUploadsByLocation(),
                    $publicationUploads,
                );
            }

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

    private function regionalPublicationUploads(): Collection
    {
        $publicationQuery = KnowledgeProduct::query()
            ->select(
                'location_id',
                DB::raw('max(coalesce(date_lastupdated, date_created)) as latest_at'),
                DB::raw('count(*) as total')
            )
            ->whereNotNull('location_id')
            ->groupBy('location_id');

        UserCountryAccess::scopeDashboard($publicationQuery);

        return $publicationQuery->get();
    }

    private function regionalRecentUploadRows(Collection $indicatorUploads, Collection $publicationUploads): Collection
    {
        $uploads = $indicatorUploads->concat($publicationUploads);

        if ($uploads->isEmpty()) {
            return collect();
        }

        $uploadLocations = Country::with([
            'parent.translations',
            'parent.parent.translations',
            'parent.parent.parent.translations',
            'translations',
        ])
            ->whereIn('location_id', $uploads->pluck('location_id')->filter()->unique())
            ->get()
            ->keyBy('location_id');

        return $uploads
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
    }

    public function getHeading(): string
    {
        if ($this->countryDashboard() instanceof Country) {
            return __('aho.charts.recent_country_indicator_uploads');
        }

        return __('aho.charts.recent_uploads_by_country');
    }

    protected function getOptions(): ?array
    {
        if (! ($this->countryDashboard() instanceof Country)) {
            return null;
        }

        return [
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'x' => [
                    'ticks' => [
                        'precision' => 0,
                    ],
                    'title' => [
                        'display' => true,
                        'text' => __('aho.fields.year'),
                    ],
                ],
                'y' => [
                    'ticks' => [
                        'autoSkip' => false,
                    ],
                ],
            ],
        ];
    }

    private function getCountryRecentIndicatorUploadsData(Country $country): array
    {
        return DashboardCache::remember('recent-country-indicator-uploads.v2.'.$country->getKey(), function () use ($country): array {
            $locationIds = CountryTableFilter::locationAndDescendantIds((int) $country->location_id);

            if ($locationIds === []) {
                return [
                    'datasets' => [[
                        'label' => __('aho.charts.reported_year'),
                        'data' => [],
                        'backgroundColor' => ['#009edb', '#0072a0', '#009a61', '#f5a623', '#6b7280'],
                    ]],
                    'labels' => [],
                ];
            }

            $models = [HealthIndicatorValue::class];

            if (Schema::connection('warehouse')->hasTable('fact_data_archive')) {
                $models[] = HealthIndicatorArchive::class;
            }

            $rows = collect($models)
                ->flatMap(fn (string $model): mixed => $model::query()
                    ->with('indicator.translations')
                    ->select([
                        'fact_id',
                        'indicator_id',
                        'location_id',
                        'period',
                        'start_period',
                        'end_period',
                        'date_created',
                        'date_lastupdated',
                    ])
                    ->whereIn('location_id', $locationIds)
                    ->whereNotNull('indicator_id')
                    ->orderByRaw('coalesce(date_lastupdated, date_created) desc')
                    ->orderByDesc('fact_id')
                    ->limit(200)
                    ->get())
                ->sortByDesc(fn (mixed $row): int => self::uploadTimestamp($row))
                ->unique('indicator_id')
                ->take(5)
                ->values();

            return [
                'datasets' => [[
                    'label' => __('aho.charts.reported_year'),
                    'data' => $rows
                        ->map(fn (mixed $row): int => self::reportedYear($row) ?? self::uploadYear($row) ?? 0)
                        ->all(),
                    'backgroundColor' => ['#009edb', '#0072a0', '#009a61', '#f5a623', '#6b7280'],
                ]],
                'labels' => $rows
                    ->map(function (mixed $row): string {
                        $indicator = $row->indicator?->display_name ?? (string) $row->indicator_id;
                        $year = self::reportedYear($row) ?? self::uploadYear($row);
                        $uploadedAt = self::uploadDateLabel($row);

                        return Str::limit($indicator, 42).' ('.($year ?: __('aho.fields.year')).', '.$uploadedAt.')';
                    })
                    ->all(),
            ];
        });
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

    private function countryDashboard(): ?Country
    {
        return self::countryDashboardForRequest();
    }

    private static function reportedYear(mixed $row): ?int
    {
        return self::extractYear($row->period)
            ?? self::extractYear($row->end_period)
            ?? self::extractYear($row->start_period);
    }

    private static function uploadYear(mixed $row): ?int
    {
        $date = $row->date_lastupdated ?? $row->date_created;

        return $date instanceof Carbon ? (int) $date->year : self::extractYear($date);
    }

    private static function uploadDateLabel(mixed $row): string
    {
        $date = $row->date_lastupdated ?? $row->date_created;

        if ($date instanceof Carbon) {
            return $date->format('Y-m-d');
        }

        return filled($date) ? Carbon::parse($date)->format('Y-m-d') : __('aho.charts.records');
    }

    private static function uploadTimestamp(mixed $row): int
    {
        $date = $row->date_lastupdated ?? $row->date_created;

        if ($date instanceof Carbon) {
            return $date->getTimestamp();
        }

        return filled($date) ? Carbon::parse($date)->getTimestamp() : 0;
    }

    private static function extractYear(mixed $value): ?int
    {
        if (blank($value)) {
            return null;
        }

        if ($value instanceof Carbon) {
            return (int) $value->year;
        }

        if (preg_match('/(?:19|20)\d{2}/', (string) $value, $matches) !== 1) {
            return null;
        }

        return (int) $matches[0];
    }
}
