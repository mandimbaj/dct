<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

class FilamentSearch
{
    /**
     * @param  array<int, string>  $columns
     * @param  array<string, array<int, string>>  $relations
     * @param  array<int, string>  $numericColumns
     * @param  array<string, array<int, string>>  $numericRelations
     */
    public static function apply(
        Builder $query,
        string $search,
        array $columns = [],
        array $relations = [],
        array $numericColumns = [],
        array $numericRelations = [],
    ): Builder {
        foreach (static::terms($search) as $term) {
            $query->where(function (Builder $query) use ($columns, $relations, $numericColumns, $numericRelations, $term): void {
                $like = "%{$term}%";

                foreach ($columns as $column) {
                    $query->orWhere($column, 'like', $like);
                }

                foreach ($relations as $relation => $fields) {
                    $query->orWhereHas($relation, function (Builder $relationQuery) use ($fields, $like): void {
                        $relationQuery->where(function (Builder $relationQuery) use ($fields, $like): void {
                            foreach ($fields as $field) {
                                $relationQuery->orWhere($field, 'like', $like);
                            }
                        });
                    });
                }

                if (! is_numeric($term)) {
                    return;
                }

                foreach ($numericColumns as $column) {
                    $query->orWhere($column, $term);
                }

                foreach ($numericRelations as $relation => $fields) {
                    $query->orWhereHas($relation, function (Builder $relationQuery) use ($fields, $term): void {
                        $relationQuery->where(function (Builder $relationQuery) use ($fields, $term): void {
                            foreach ($fields as $field) {
                                $relationQuery->orWhere($field, $term);
                            }
                        });
                    });
                }
            });
        }

        return $query;
    }

    /**
     * @return array<int, string>
     */
    private static function terms(string $search): array
    {
        return array_values(array_filter(
            preg_split('/\s+/u', trim($search)) ?: [],
            fn (string $term): bool => $term !== '',
        ));
    }
}
