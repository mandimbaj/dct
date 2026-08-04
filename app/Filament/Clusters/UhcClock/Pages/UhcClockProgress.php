<?php

namespace App\Filament\Clusters\UhcClock\Pages;

use App\Filament\Clusters\UhcClock;
use App\Services\UhcClock\UhcTargetAttainmentService;
use App\Support\UserPermissions;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class UhcClockProgress extends Page
{
    protected static ?string $cluster = UhcClock::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    protected static ?string $slug = 'progress';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.clusters.uhc-clock.pages.uhc-clock-progress';

    private ?array $cachedSummary = null;

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.uhc_clock_progress.navigation');
    }

    public function getTitle(): string
    {
        return __('aho.resources.uhc_clock_progress.navigation');
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user() && UserPermissions::allowsPage(auth()->user(), static::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        return $this->cachedSummary ??= app(UhcTargetAttainmentService::class)->summary();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function countries(): array
    {
        return $this->summary()['countries'] ?? [];
    }

    public function levelMetric(array $country, string $level): string
    {
        $levelSummary = $country['levels'][$level] ?? [];
        $metric = in_array($level, ['day', 'hour'], true)
            ? ($levelSummary['apc_remaining_average'] ?? null)
            : ($levelSummary['change_average'] ?? null);

        return $this->formatPercent($metric);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function countryDetailPayloads(): array
    {
        return collect($this->countries())
            ->mapWithKeys(fn (array $country): array => [
                (string) ($country['location_id'] ?? '') => $this->countryDetailPayload($country),
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function countryDetailPayload(array $country): array
    {
        return [
            'country' => $country['country'] ?? '',
            'selected' => number_format($country['selected'] ?? 0),
            'assessed' => number_format($country['assessed'] ?? 0),
            'notEvaluable' => number_format($country['not_evaluable'] ?? 0),
            'targetRatio' => $this->targetRatio($country),
            'levels' => collect(['day', 'hour', 'minute', 'second'])
                ->map(fn (string $level): array => $this->levelDetailPayload($country, $level))
                ->all(),
            'assessedExamples' => collect($country['details']['assessed_examples'] ?? [])
                ->map(fn (array $example): array => $this->assessedExamplePayload($example))
                ->all(),
            'missingExamples' => collect($country['details']['missing_examples'] ?? [])
                ->map(fn (array $example): array => $this->missingExamplePayload($example))
                ->all(),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function levelDetailPayload(array $country, string $level): array
    {
        $levelSummary = $country['levels'][$level] ?? [];

        return [
            'name' => __('aho.uhc_attainment.levels.'.$level),
            'metric' => $this->levelMetric($country, $level),
            'selected' => number_format($levelSummary['selected'] ?? 0),
            'assessed' => number_format($levelSummary['assessed'] ?? 0),
            'notEvaluable' => number_format($levelSummary['not_evaluable'] ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function assessedExamplePayload(array $example): array
    {
        $targetStatus = $example['target_reached'] === null
            ? __('aho.uhc_attainment.no_target')
            : ($example['target_reached'] ? __('aho.uhc_attainment.achieved') : __('aho.uhc_attainment.below_target'));

        return [
            'title' => $this->indicatorTitle($example),
            'level' => __('aho.uhc_attainment.levels.'.($example['level'] ?? 'second')),
            'baseline' => $this->valueLinePayload($example, 'baseline', __('aho.uhc_attainment.detail_baseline_label', [
                'period' => $example['baseline_period'] ?? 'N/A',
            ])),
            'current' => $this->valueLinePayload($example, 'current', __('aho.uhc_attainment.detail_current_label', [
                'period' => $example['current_period'] ?? 'N/A',
            ])),
            'change' => __('aho.uhc_attainment.detail_change', [
                'value' => $this->formatPercentValue($example['change'] ?? null),
            ]),
            'remaining' => __('aho.uhc_attainment.detail_remaining', [
                'value' => $this->formatPercentValue($example['apc_remaining'] ?? null),
            ]),
            'target' => $targetStatus,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function valueLinePayload(array $example, string $prefix, string $label): array
    {
        return [
            'label' => $label,
            'value' => $this->formatNumber($example[$prefix.'_value'] ?? null),
            'tooltip' => $this->dataSourceTooltip($example, $prefix),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function missingExamplePayload(array $example): array
    {
        return [
            'title' => $this->indicatorTitle($example),
            'level' => __('aho.uhc_attainment.levels.'.($example['level'] ?? 'second')),
            'reason' => $this->missingReasonLabel($example['missing_reason'] ?? null),
            'facts' => __('aho.uhc_attainment.detail_available_rows', [
                'count' => number_format($example['facts_count'] ?? 0),
            ]),
        ];
    }

    private function indicatorTitle(array $example): string
    {
        $code = trim((string) ($example['indicator_code'] ?? ''));
        $name = trim((string) ($example['indicator_name'] ?? ''));

        return trim($code.' - '.$name, ' -') ?: __('aho.uhc_attainment.unknown_indicator');
    }

    public function targetRatio(array $summary): string
    {
        if (($summary['target_evaluable'] ?? 0) === 0) {
            return 'N/A';
        }

        return ($summary['achieved'] ?? 0).'/'.$summary['target_evaluable'];
    }

    public function formatPercent(?float $value): string
    {
        return $value === null ? 'N/A' : number_format($value, 1).'%';
    }

    private function formatPercentValue(mixed $value): string
    {
        return is_numeric($value) ? number_format((float) $value, 1).'%' : 'N/A';
    }

    private function formatNumber(mixed $value): string
    {
        if (! is_numeric($value)) {
            return 'N/A';
        }

        $number = (float) $value;

        return abs($number - round($number)) < 0.000001
            ? number_format($number, 0)
            : number_format($number, 2);
    }

    private function dataSourceTooltip(array $example, string $prefix): string
    {
        $sourceName = trim((string) ($example[$prefix.'_datasource_name'] ?? ''));

        return __('aho.uhc_attainment.datasource_tooltip', [
            'datasource' => $sourceName !== '' ? $sourceName : __('aho.uhc_attainment.datasource_unknown'),
        ]);
    }

    private function missingReasonLabel(?string $reason): string
    {
        return match ($reason) {
            'missing_baseline' => __('aho.uhc_attainment.missing_baseline'),
            'missing_recent' => __('aho.uhc_attainment.missing_recent'),
            'missing_baseline_and_current' => __('aho.uhc_attainment.missing_baseline_and_current'),
            'zero_baseline' => __('aho.uhc_attainment.zero_baseline'),
            default => __('aho.uhc_attainment.no_values'),
        };
    }
}
