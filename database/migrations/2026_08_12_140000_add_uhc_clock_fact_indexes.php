<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<string, array{table: string, columns: array<int, string>}>
     */
    private array $indexes = [
        'idx_uhc_live_indicator_location_period' => [
            'table' => 'fact_data_indicators',
            'columns' => ['indicator_id', 'location_id', 'end_period', 'start_period', 'datasource_id', 'date_lastupdated', 'fact_id'],
        ],
        'idx_uhc_archive_indicator_location_period' => [
            'table' => 'fact_data_archive',
            'columns' => ['indicator_id', 'location_id', 'end_period', 'start_period', 'datasource_id', 'date_lastupdated', 'fact_id'],
        ],
    ];

    public function up(): void
    {
        if (app()->environment('testing')) {
            return;
        }

        foreach ($this->indexes as $index => $definition) {
            $this->addWarehouseIndex($definition['table'], $index, $definition['columns']);
        }
    }

    public function down(): void
    {
        if (app()->environment('testing')) {
            return;
        }

        foreach ($this->indexes as $index => $definition) {
            $this->dropWarehouseIndex($definition['table'], $index);
        }
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function addWarehouseIndex(string $table, string $index, array $columns): void
    {
        try {
            if (! $this->hasRequiredColumns($table, $columns) || $this->warehouseIndexExists($table, $index)) {
                return;
            }

            DB::connection('warehouse')->statement(sprintf(
                'alter table `%s` add index `%s` (%s)',
                $table,
                $index,
                implode(', ', array_map(fn (string $column): string => "`{$column}`", $columns)),
            ));
        } catch (Throwable $exception) {
            if (app()->environment('local')) {
                throw $exception;
            }
        }
    }

    private function dropWarehouseIndex(string $table, string $index): void
    {
        try {
            if (! Schema::connection('warehouse')->hasTable($table) || ! $this->warehouseIndexExists($table, $index)) {
                return;
            }

            DB::connection('warehouse')->statement("alter table `{$table}` drop index `{$index}`");
        } catch (Throwable $exception) {
            if (app()->environment('local')) {
                throw $exception;
            }
        }
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function hasRequiredColumns(string $table, array $columns): bool
    {
        if (! Schema::connection('warehouse')->hasTable($table)) {
            return false;
        }

        foreach ($columns as $column) {
            if (! Schema::connection('warehouse')->hasColumn($table, $column)) {
                return false;
            }
        }

        return true;
    }

    private function warehouseIndexExists(string $table, string $index): bool
    {
        if (! Schema::connection('warehouse')->hasTable($table)) {
            return false;
        }

        return DB::connection('warehouse')
            ->select("show index from `{$table}` where Key_name = ?", [$index]) !== [];
    }
};
