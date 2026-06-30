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
     * Columns that can meaningfully identify an option in a search field.
     *
     * @var array<int, string>
     */
    private const SEARCHABLE_COLUMNS = [
        'name',
        'shortname',
        'title',
        'label',
        'code',
        'afrocode',
        'gen_code',
        'iso_alpha',
        'iso_number',
    ];

    /**
     * @return array<int|string, string>
     */
    public static function fromDisplayNameQuery(Builder $query, ?string $search = null, ?string $keyName = null): array
    {
        $model = $query->getModel();
        self::applyContainsSearch($query, $search);

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

        return str_contains(self::normalize($label), self::normalize($search));
    }

    private static function applyContainsSearch(Builder $query, ?string $search): void
    {
        $search = trim((string) $search);

        if ($search === '') {
            return;
        }

        $model = $query->getModel();
        $modelColumns = self::searchableColumns($model);
        $translationColumns = [];

        if (method_exists($model, 'translations')) {
            try {
                $translationColumns = self::searchableColumns(
                    $model->translations()->getRelated(),
                    assumeNameColumn: true,
                );
            } catch (Throwable) {
                $translationColumns = [];
            }
        }

        if ($modelColumns === [] && $translationColumns === []) {
            return;
        }

        $query->where(function (Builder $searchQuery) use ($model, $modelColumns, $translationColumns, $search): void {
            $isFirstConstraint = true;

            foreach ($modelColumns as $column) {
                $method = $isFirstConstraint ? 'where' : 'orWhere';
                $searchQuery->{$method}($model->qualifyColumn($column), 'like', "%{$search}%");
                $isFirstConstraint = false;
            }

            if ($translationColumns === []) {
                return;
            }

            $translationSearch = function (Builder $translationQuery) use ($translationColumns, $search): void {
                $translationModel = $translationQuery->getModel();

                $translationQuery->where(function (Builder $columnQuery) use ($translationModel, $translationColumns, $search): void {
                    foreach ($translationColumns as $index => $column) {
                        $method = $index === 0 ? 'where' : 'orWhere';
                        $columnQuery->{$method}($translationModel->qualifyColumn($column), 'like', "%{$search}%");
                    }
                });
            };

            if ($isFirstConstraint) {
                $searchQuery->whereHas('translations', $translationSearch);

                return;
            }

            $searchQuery->orWhereHas('translations', $translationSearch);
        });

    }

    /**
     * @return array<int, string>
     */
    private static function searchableColumns(Model $model, bool $assumeNameColumn = false): array
    {
        $columns = array_values(array_intersect(self::SEARCHABLE_COLUMNS, $model->getFillable()));

        if ($columns === [] && $assumeNameColumn) {
            return ['name'];
        }

        return $columns;
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
