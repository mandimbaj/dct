<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'is_country_admin')) {
                $table->boolean('is_country_admin')->default(false)->after('is_super_admin');
            }
        });

        $this->addApprovalColumns('fact_data_indicators');
        $this->addApprovalColumns('stg_knowledge_product');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropApprovalColumns('fact_data_indicators');
        $this->dropApprovalColumns('stg_knowledge_product');

        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'is_country_admin')) {
                $table->dropColumn('is_country_admin');
            }
        });
    }

    private function addApprovalColumns(string $tableName): void
    {
        $this->withWarehouse(function () use ($tableName): void {
            if (! Schema::connection('warehouse')->hasTable($tableName)) {
                return;
            }

            Schema::connection('warehouse')->table($tableName, function (Blueprint $table) use ($tableName): void {
                if (! Schema::connection('warehouse')->hasColumn($tableName, 'approval_status')) {
                    $table->string('approval_status', 30)->default('pending');
                }

                if (! Schema::connection('warehouse')->hasColumn($tableName, 'approved_by')) {
                    $table->unsignedBigInteger('approved_by')->nullable();
                }

                if (! Schema::connection('warehouse')->hasColumn($tableName, 'approved_at')) {
                    $table->timestamp('approved_at')->nullable();
                }
            });
        });
    }

    private function dropApprovalColumns(string $tableName): void
    {
        $this->withWarehouse(function () use ($tableName): void {
            if (! Schema::connection('warehouse')->hasTable($tableName)) {
                return;
            }

            Schema::connection('warehouse')->table($tableName, function (Blueprint $table) use ($tableName): void {
                foreach (['approval_status', 'approved_by', 'approved_at'] as $column) {
                    if (Schema::connection('warehouse')->hasColumn($tableName, $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        });
    }

    private function withWarehouse(callable $callback): void
    {
        try {
            $callback();
        } catch (Throwable $exception) {
            if (app()->environment('testing')) {
                return;
            }

            throw $exception;
        }
    }
};
