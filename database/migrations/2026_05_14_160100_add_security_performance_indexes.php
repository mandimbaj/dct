<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->index('is_super_admin', 'users_is_super_admin_index');
            $table->index(['location_id', 'is_country_admin'], 'users_location_country_admin_index');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex('users_is_super_admin_index');
            $table->dropIndex('users_location_country_admin_index');
        });
    }
};
