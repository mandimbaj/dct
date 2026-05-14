<?php

use App\Support\ApprovalWorkflow;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (app()->environment('testing')) {
            return;
        }

        $this->syncWarehouseTable('fact_data_indicators');
        $this->syncWarehouseTable('stg_knowledge_product');
    }

    public function down(): void
    {
        //
    }

    private function syncWarehouseTable(string $table): void
    {
        try {
            if (! Schema::connection('warehouse')->hasTable($table)
                || ! Schema::connection('warehouse')->hasColumn($table, ApprovalWorkflow::STATUS_COLUMN)
                || ! Schema::connection('warehouse')->hasColumn($table, ApprovalWorkflow::MIRROR_COLUMN)
            ) {
                return;
            }

            DB::connection('warehouse')
                ->table($table)
                ->whereIn(ApprovalWorkflow::STATUS_COLUMN, [
                    ApprovalWorkflow::STATUS_PENDING,
                    ApprovalWorkflow::STATUS_APPROVED,
                    ApprovalWorkflow::STATUS_REJECTED,
                ])
                ->update([
                    ApprovalWorkflow::MIRROR_COLUMN => DB::raw(ApprovalWorkflow::STATUS_COLUMN),
                ]);
        } catch (Throwable) {
            return;
        }
    }
};
