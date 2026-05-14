<?php

namespace App\Services\DataQuality;

use App\Models\HealthIndicatorValue;
use App\Models\Indicator;
use App\Support\TextEncoding;
use App\Support\UserCountryAccess;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DataQualityService
{
    /**
     * @return array<int, array{severity: string, rule: string, message: string}>
     */
    public function inspectIndicatorPayload(array $data): array
    {
        $indicator = Indicator::with('translations')->find($data['indicator_id'] ?? null);

        if (! $indicator) {
            return [];
        }

        return $this->inspectIndicatorValue(
            indicator: $indicator,
            value: $data['value_received'] ?? null,
            numerator: $data['numerator_value'] ?? null,
            denominator: $data['denominator_value'] ?? null,
        );
    }

    /**
     * @return array<int, array{severity: string, rule: string, message: string}>
     */
    public function inspectIndicatorValue(
        Indicator $indicator,
        mixed $value,
        mixed $numerator = null,
        mixed $denominator = null,
    ): array {
        $issues = [];
        $indicatorText = $this->indicatorText($indicator);
        $numericValue = is_numeric($value) ? (float) $value : null;

        if ($numericValue !== null && $numericValue < 0) {
            $issues[] = $this->issue('error', 'negative_value', __('aho.quality.negative_value'));
        }

        if ($this->looksLikePercentage($indicatorText)) {
            foreach (['value_received' => $numericValue] as $field => $candidate) {
                if ($candidate !== null && $candidate > 100) {
                    $issues[] = $this->issue('error', 'percentage_above_100', __('aho.quality.percentage_above_100', [
                        'field' => __("aho.fields.{$field}"),
                    ]));
                }

                if ($candidate !== null && $candidate > 0 && $candidate <= 1) {
                    $issues[] = $this->issue('warning', 'percentage_looks_like_index', __('aho.quality.percentage_looks_like_index', [
                        'field' => __("aho.fields.{$field}"),
                    ]));
                }
            }
        }

        if ($this->looksLikeIndex($indicatorText) && $numericValue !== null && $numericValue > 100) {
            $issues[] = $this->issue('warning', 'index_above_expected_range', __('aho.quality.index_above_expected_range'));
        }

        if (is_numeric($numerator) && is_numeric($denominator) && (float) $denominator === 0.0 && (float) $numerator > 0) {
            $issues[] = $this->issue('error', 'zero_denominator', __('aho.quality.zero_denominator'));
        }

        return $issues;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function scanIndicatorValues(int $limit = 50): Collection
    {
        $query = HealthIndicatorValue::query()
            ->with(['indicator.translations', 'location.translations'])
            ->where(function ($query): void {
                $query
                    ->where('value_received', '<', 0)
                    ->orWhere(function ($query): void {
                        $query
                            ->whereIn('indicator_id', $this->percentageIndicatorIds())
                            ->where(function ($query): void {
                                $query
                                    ->where('value_received', '>', 100)
                                    ->orWhereBetween('value_received', [0.000001, 1]);
                            });
                    })
                    ->orWhere(function ($query): void {
                        $query
                            ->whereNotNull('denominator_value')
                            ->where('denominator_value', 0)
                            ->where('numerator_value', '>', 0);
                    });
            })
            ->latest('fact_id')
            ->limit($limit);

        UserCountryAccess::scope($query);

        return $query->get()
            ->flatMap(function (HealthIndicatorValue $record): array {
                $issues = $this->inspectIndicatorValue(
                    indicator: $record->indicator,
                    value: $record->value_received,
                    numerator: $record->numerator_value,
                    denominator: $record->denominator_value,
                );

                return array_map(fn (array $issue): array => [
                    ...$issue,
                    'fact_id' => $record->fact_id,
                    'indicator' => $record->indicator?->display_name,
                    'location' => $record->location?->display_name,
                    'period' => $record->period,
                    'value' => $record->value_received,
                ], $issues);
            })
            ->values();
    }

    /**
     * @return array<string, int>
     */
    public function summary(): array
    {
        $issues = $this->scanIndicatorValues(250);

        return [
            'errors' => $issues->where('severity', 'error')->count(),
            'warnings' => $issues->where('severity', 'warning')->count(),
            'checked' => 250,
        ];
    }

    private function indicatorText(Indicator $indicator): string
    {
        $parts = $indicator->translations
            ->flatMap(fn ($translation): array => [
                TextEncoding::clean($translation->name),
                TextEncoding::clean($translation->shortname),
                TextEncoding::clean($translation->definition),
            ])
            ->filter()
            ->all();

        return Str::lower(implode(' ', $parts));
    }

    private function looksLikePercentage(string $indicatorText): bool
    {
        if (str_contains($indicatorText, '%')) {
            return true;
        }

        return Str::contains($indicatorText, [
            'percentage',
            'percent',
            'proportion',
            'coverage',
            'couverture',
            'pourcentage',
            'proporcao',
            'percentagem',
        ]);
    }

    private function looksLikeIndex(string $indicatorText): bool
    {
        return Str::contains($indicatorText, ['index', 'indice', 'score']);
    }

    /**
     * @return array<int, int>
     */
    private function percentageIndicatorIds(): array
    {
        return Indicator::query()
            ->whereHas('translations', function ($query): void {
                $query
                    ->whereRaw('name like ?', ['%\\%%'])
                    ->orWhere('name', 'like', '%percentage%')
                    ->orWhere('name', 'like', '%percent%')
                    ->orWhere('name', 'like', '%proportion%')
                    ->orWhere('name', 'like', '%coverage%')
                    ->orWhereRaw('definition like ?', ['%\\%%'])
                    ->orWhere('definition', 'like', '%percentage%')
                    ->orWhere('definition', 'like', '%percent%')
                    ->orWhere('definition', 'like', '%proportion%')
                    ->orWhere('definition', 'like', '%coverage%');
            })
            ->pluck('indicator_id')
            ->all();
    }

    /**
     * @return array{severity: string, rule: string, message: string}
     */
    private function issue(string $severity, string $rule, string $message): array
    {
        return compact('severity', 'rule', 'message');
    }
}
