<?php

namespace App\Support;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FilamentReadOnlyTables
{
    public static function translatedReference(Table $table, string $idColumn, string $nameLabel = 'name'): Table
    {
        return $table
            ->defaultSort($idColumn)
            ->searchUsing(function (Builder $query, string $search) use ($idColumn): void {
                FilamentSearch::apply(
                    query: $query,
                    search: $search,
                    columns: ['code'],
                    relations: ['translations' => ['name', 'shortname', 'description']],
                    numericColumns: [$idColumn],
                );
            })
            ->columns([
                self::column($idColumn, 'id')->sortable()->toggleable(isToggledHiddenByDefault: true),
                self::column('display_name', $nameLabel)->wrap()->sortable(),
                self::column('code', 'code')->sortable()->toggleable(),
                self::column('date_created', 'creation')->dateTime()->sortable()->toggleable(),
                self::column('date_lastupdated', 'modification')->dateTime()->sortable()->toggleable(),
            ]);
    }

    /**
     * @param  array<string, string>  $columns
     * @param  array<int, string>  $searchColumns
     * @param  array<int, string>  $numericColumns
     */
    public static function simple(
        Table $table,
        array $columns,
        string $defaultSort,
        array $searchColumns = [],
        array $numericColumns = [],
        string $direction = 'asc',
    ): Table {
        $searchColumns = $searchColumns === [] ? array_keys($columns) : $searchColumns;

        return $table
            ->defaultSort($defaultSort, $direction)
            ->searchUsing(function (Builder $query, string $search) use ($searchColumns, $numericColumns): void {
                FilamentSearch::apply(
                    query: $query,
                    search: $search,
                    columns: $searchColumns,
                    numericColumns: $numericColumns,
                );
            })
            ->columns(collect($columns)
                ->map(fn (string $label, string $column): TextColumn => self::column($column, $label)->wrap()->sortable()->toggleable())
                ->values()
                ->all());
    }

    private static function column(string $name, string $label): TextColumn
    {
        return TextColumn::make($name)
            ->label(__("aho.fields.{$label}"));
    }
}
