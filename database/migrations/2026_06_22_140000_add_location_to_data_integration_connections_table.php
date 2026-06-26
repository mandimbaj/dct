<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('data_integration_connections', 'location_id')) {
            Schema::table('data_integration_connections', function (Blueprint $table): void {
                $table->unsignedBigInteger('location_id')->nullable()->index();
            });
        }

        DB::table('data_integration_connections')
            ->leftJoin('users', 'users.id', '=', 'data_integration_connections.user_id')
            ->whereNull('data_integration_connections.location_id')
            ->whereNotNull('users.location_id')
            ->get(['data_integration_connections.id', 'users.location_id'])
            ->each(function (object $connection): void {
                DB::table('data_integration_connections')
                    ->where('id', $connection->id)
                    ->update(['location_id' => $connection->location_id]);
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('data_integration_connections', 'location_id')) {
            Schema::table('data_integration_connections', function (Blueprint $table): void {
                $table->dropColumn('location_id');
            });
        }
    }
};
