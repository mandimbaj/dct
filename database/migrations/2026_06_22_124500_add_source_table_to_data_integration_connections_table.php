<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_integration_connections', function (Blueprint $table): void {
            $table->string('source_table')->nullable();
        });

        DB::table('data_integration_connections')
            ->select(['id', 'data_scope'])
            ->orderBy('id')
            ->each(function (object $connection): void {
                $scope = is_string($connection->data_scope)
                    ? json_decode($connection->data_scope, true)
                    : $connection->data_scope;
                $sourceTable = is_array($scope) ? ($scope['source_table'] ?? null) : null;

                if (filled($sourceTable)) {
                    DB::table('data_integration_connections')
                        ->where('id', $connection->id)
                        ->update(['source_table' => (string) $sourceTable]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('data_integration_connections', function (Blueprint $table): void {
            $table->dropColumn('source_table');
        });
    }
};
