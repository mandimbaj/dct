<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Throwable;

final class SelectOptions
{
    public const LIMIT = 10000;

    /**
     * @return array<int|string, string>
     */
    public static function fromDisplayNameQuery(Builder $query, ?string $search = null, ?string $keyName = null): array
    {
        $model = $query->getModel();

        if (method_exists($model, 'translations')) {
            $query->with('translations');
        }

        return self::fromRecords(
            $query->limit(self::LIMIT)->get(),
            $keyName ?? $model->getKeyName(),
            $search,
        );
    }

    public static function orderByDisplayName(Builder $query, ?string $fallbackColumn = null): Builder
    {
        $model = $query->getModel();

        if (! method_exists($model, 'translations')) {
            return $query->orderBy($fallbackColumn ?? $model->getKeyName());
        }

        try {
            $relation = $model->translations();
            $translationModel = $relation->getRelated();
            $translationTable = $translationModel->getTable();
            $languageOrder = collect(WarehouseLocale::preferredLanguages())
                ->map(fn (string $language, int $index): string => "WHEN language_code = '".str_replace("'", "''", $language)."' THEN {$index}")
                ->implode(' ');

            $nameSubquery = $translationModel::query()
                ->select('name')
                ->whereColumn(
                    "{$translationTable}.{$relation->getForeignKeyName()}",
                    $model->qualifyColumn($relation->getLocalKeyName()),
                )
                ->whereIn('language_code', WarehouseLocale::preferredLanguages())
                ->whereNotNull('name')
                ->orderByRaw("CASE {$languageOrder} ELSE 999 END")
                ->limit(1);

            return $query
                ->with('translations')
                ->orderBy($nameSubquery)
                ->orderBy($model->qualifyColumn($fallbackColumn ?? self::fallbackColumn($model)));
        } catch (Throwable) {
            return $query->orderBy($model->qualifyColumn($fallbackColumn ?? $model->getKeyName()));
        }
    }

    /**
     * @param  array<int|string, string>  $options
     * @return array<int|string, string>
     */
    public static function filterAndSort(array $options, ?string $search = null): array
    {
        return collect($options)
            ->filter(fn (string $label): bool => self::matchesSearch($label, $search))
            ->sortBy(fn (string $label): string => self::normalize($label), SORT_NATURAL)
            ->take(self::LIMIT)
            ->all();
    }

    /**
     * @param  iterable<int, Model>  $records
     * @return array<int|string, string>
     */
    private static function fromRecords(iterable $records, string $keyName, ?string $search = null): array
    {
        return collect($records)
            ->map(fn (Model $record): array => [
                'key' => $record->getAttribute($keyName) ?? $record->getKey(),
                'label' => self::label($record),
            ])
            ->filter(fn (array $option): bool => filled($option['key']) && self::matchesSearch($option['label'], $search))
            ->sortBy(fn (array $option): string => self::normalize($option['label']), SORT_NATURAL)
            ->take(self::LIMIT)
            ->mapWithKeys(fn (array $option): array => [$option['key'] => $option['label']])
            ->all();
    }

    private static function label(Model $record): string
    {
        foreach (['display_name', 'name', 'title', 'label', 'shortname', 'code'] as $attribute) {
            $value = $record->getAttribute($attribute);

            if (filled($value)) {
                return TextEncoding::clean((string) $value) ?? (string) $value;
            }
        }

        return (string) $record->getKey();
    }

    private static function matchesSearch(string $label, ?string $search): bool
    {
        if (blank($search)) {
            return true;
        }

        return str_starts_with(self::normalize($label), self::normalize($search));
    }

    private static function normalize(string $value): string
    {
        return Str::lower(Str::ascii(trim(preg_replace('/\s+/', ' ', $value) ?? $value)));
    }

    private static function fallbackColumn(Model $model): string
    {
        foreach (['code', 'afrocode', 'name', 'title'] as $column) {
            if (array_key_exists($column, $model->getAttributes())) {
                return $column;
            }
        }

        return $model->getKeyName();
    }
}
