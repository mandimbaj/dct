<?php

namespace App\Support\DataQuality;

use App\Filament\Resources\HealthIndicatorValues\HealthIndicatorValueResource;
use App\Models\DataQuality\DqaExternalConsistency;
use App\Models\DataQuality\DqaInternalConsistency;
use App\Models\DataQuality\DqaInvalidCategoryOption;
use App\Models\DataQuality\DqaInvalidDataSource;
use App\Models\DataQuality\DqaInvalidMeasureType;
use App\Models\DataQuality\DqaInvalidPeriod;
use App\Models\DataQuality\DqaMissingValue;
use App\Models\DataQuality\DqaMultipleMeasure;
use App\Models\DataQuality\DqaReportModel;
use App\Models\DataQuality\DqaValueTypeConsistency;
use App\Models\HealthIndicatorValue;
use App\Support\TextEncoding;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Throwable;

class DqaIssueResolver
{
    /**
     * @var array<string, int|null>
     */
    private static array $resolvedFactIds = [];

    /**
     * @return array<int, class-string<DqaReportModel>>
     */
    public static function reportModels(): array
    {
        return [
            DqaMissingValue::class,
            DqaMultipleMeasure::class,
            DqaInvalidCategoryOption::class,
            DqaInvalidDataSource::class,
            DqaInvalidMeasureType::class,
            DqaInvalidPeriod::class,
            DqaExternalConsistency::class,
            DqaInternalConsistency::class,
            DqaValueTypeConsistency::class,
        ];
    }

    public static function correctionUrl(DqaReportModel $issue): ?string
    {
        $factId = self::factIdForIssue($issue);

        if ($factId === null) {
            return null;
        }

        return self::correctionUrlForFactId($factId);
    }

    public static function correctionUrlForFactId(int $factId): string
    {
        return HealthIndicatorValueResource::getUrl('edit', [
            'country' => self::countryParameter(),
            'record' => $factId,
        ]);
    }

    public static function factIdForIssue(DqaReportModel $issue): ?int
    {
        $cacheKey = $issue::class.':'.$issue->getKey();

        if (array_key_exists($cacheKey, self::$resolvedFactIds)) {
            return self::$resolvedFactIds[$cacheKey];
        }

        $record = self::queryForIssue($issue)
            ->latest('fact_id')
            ->first(['fact_id']);

        return self::$resolvedFactIds[$cacheKey] = $record?->fact_id ? (int) $record->fact_id : null;
    }

    public static function deleteResolvedIssuesForValue(HealthIndicatorValue $value, ?array $previousSignature = null): void
    {
        foreach (array_filter([$previousSignature, self::signatureForValue($value)]) as $signature) {
            foreach (self::reportModels() as $modelClass) {
                try {
                    self::applySignature($modelClass::query(), $signature)->delete();
                } catch (Throwable) {
                    // Some DQA sources may be database views. Corrections must not block the saved value.
                }
            }
        }

        self::$resolvedFactIds = [];
    }

    /**
     * @return array<string, array<int, string>|string|null>
     */
    public static function signatureForValue(HealthIndicatorValue $value): array
    {
        $value->loadMissing([
            'indicator.translations',
            'location.translations',
            'categoryOption.translations',
            'dataSource.translations',
            'measureMethod.translations',
        ]);

        return [
            'indicator_name' => self::terms([
                $value->indicator?->afrocode,
                $value->indicator?->gen_code,
                $value->indicator?->display_name,
                ...($value->indicator?->translations?->pluck('name')->all() ?? []),
                ...($value->indicator?->translations?->pluck('shortname')->all() ?? []),
            ]),
            'location' => self::terms([
                $value->location?->code,
                $value->location?->iso_alpha,
                $value->location?->display_name,
                ...($value->location?->translations?->pluck('name')->all() ?? []),
            ]),
            'categoryoption' => self::terms([
                $value->categoryOption?->code,
                $value->categoryOption?->display_name,
                ...($value->categoryOption?->translations?->pluck('name')->all() ?? []),
            ]),
            'datasource' => self::terms([
                $value->dataSource?->code,
                $value->dataSource?->display_name,
                ...($value->dataSource?->translations?->pluck('name')->all() ?? []),
                ...($value->dataSource?->translations?->pluck('shortname')->all() ?? []),
            ]),
            'measure_type' => self::terms([
                $value->measureMethod?->code,
                $value->measureMethod?->display_name,
                ...($value->measureMethod?->translations?->pluck('name')->all() ?? []),
                ...($value->measureMethod?->translations?->pluck('measure_value')->all() ?? []),
            ]),
            'period' => self::clean($value->period),
            'value' => self::clean($value->value_received ?? $value->string_value ?? '-'),
        ];
    }

    private static function queryForIssue(DqaReportModel $issue): Builder
    {
        $query = HealthIndicatorValue::query()
            ->with([
                'indicator.translations',
                'location.translations',
                'categoryOption.translations',
                'dataSource.translations',
                'measureMethod.translations',
            ]);

        self::applyIssuePeriodAndValue($query, $issue);
        self::whereIndicator($query, self::clean($issue->indicator_name));
        self::whereLocation($query, self::clean($issue->location));
        self::whereCategoryOption($query, self::clean($issue->categoryoption));
        self::whereDataSource($query, self::clean($issue->datasource));
        self::whereMeasureMethod($query, self::clean($issue->measure_type));

        return $query;
    }

    private static function applyIssuePeriodAndValue(Builder $query, DqaReportModel $issue): void
    {
        $period = self::clean($issue->period);

        if ($period !== '') {
            $query->where('period', $period);
        }

        $value = self::clean($issue->value);

        if ($value === '' || $value === '-') {
            return;
        }

        $query->where(function (Builder $query) use ($value): void {
            $query->where('string_value', $value);

            if (is_numeric($value)) {
                $query->orWhere('value_received', (float) $value);
            }
        });
    }

    /**
     * @param  array<string, array<int, string>|string|null>  $signature
     */
    private static function applySignature(Builder $query, array $signature): Builder
    {
        foreach (['indicator_name', 'location', 'categoryoption', 'datasource', 'measure_type'] as $column) {
            $terms = (array) ($signature[$column] ?? []);

            if ($terms !== []) {
                $query->whereIn($column, $terms);
            }
        }

        if (filled($signature['period'] ?? null)) {
            $query->where('period', $signature['period']);
        }

        $value = $signature['value'] ?? null;

        if (filled($value)) {
            $query->where(function (Builder $query) use ($value): void {
                $query->where('value', $value);

                if ((string) $value !== '-') {
                    $query->orWhere('value', '-');
                }
            });
        }

        return $query;
    }

    private static function whereIndicator(Builder $query, string $needle): void
    {
        if ($needle === '') {
            return;
        }

        $query->whereHas('indicator', function (Builder $query) use ($needle): void {
            $query->where('afrocode', $needle)
                ->orWhere('gen_code', $needle)
                ->orWhereHas('translations', function (Builder $query) use ($needle): void {
                    $query->where('name', $needle)
                        ->orWhere('shortname', $needle);
                });
        });
    }

    private static function whereLocation(Builder $query, string $needle): void
    {
        if ($needle === '') {
            return;
        }

        $query->whereHas('location', function (Builder $query) use ($needle): void {
            $query->where('code', $needle)
                ->orWhere('iso_alpha', $needle)
                ->orWhereHas('translations', fn (Builder $query) => $query->where('name', $needle));
        });
    }

    private static function whereCategoryOption(Builder $query, string $needle): void
    {
        if ($needle === '') {
            return;
        }

        $query->whereHas('categoryOption', function (Builder $query) use ($needle): void {
            $query->where('code', $needle)
                ->orWhereHas('translations', fn (Builder $query) => $query->where('name', $needle));
        });
    }

    private static function whereDataSource(Builder $query, string $needle): void
    {
        if ($needle === '') {
            return;
        }

        $query->whereHas('dataSource', function (Builder $query) use ($needle): void {
            $query->where('code', $needle)
                ->orWhereHas('translations', function (Builder $query) use ($needle): void {
                    $query->where('name', $needle)
                        ->orWhere('shortname', $needle);
                });
        });
    }

    private static function whereMeasureMethod(Builder $query, string $needle): void
    {
        if ($needle === '') {
            return;
        }

        $query->whereHas('measureMethod', function (Builder $query) use ($needle): void {
            $query->where('code', $needle)
                ->orWhereHas('translations', function (Builder $query) use ($needle): void {
                    $query->where('name', $needle)
                        ->orWhere('measure_value', $needle);
                });
        });
    }

    /**
     * @param  array<int, mixed>  $values
     * @return array<int, string>
     */
    private static function terms(array $values): array
    {
        return Collection::make($values)
            ->map(fn (mixed $value): string => self::clean($value))
            ->filter(fn (string $value): bool => $value !== '')
            ->flatMap(fn (string $value): array => [$value, strtoupper($value)])
            ->unique()
            ->values()
            ->all();
    }

    private static function clean(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return TextEncoding::clean((string) $value) ?? trim((string) $value);
    }

    private static function countryParameter(): string
    {
        $routeCountry = request()->route('country');

        if (filled($routeCountry)) {
            return (string) $routeCountry;
        }

        if (request()->hasSession()) {
            $sessionCountry = request()->session()->get('admin_country');

            if (filled($sessionCountry)) {
                return (string) $sessionCountry;
            }
        }

        $iso = optional(auth()->user()?->location)->iso_alpha;

        return filled($iso) ? strtolower(substr(trim((string) $iso), 0, 2)) : 'global';
    }
}
