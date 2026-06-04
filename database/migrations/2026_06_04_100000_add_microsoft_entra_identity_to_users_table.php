<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('entra_tenant_id', 64)->nullable();
            $table->string('entra_object_id', 64)->nullable();
            $table->string('entra_user_principal_name')->nullable();
            $table->timestamp('entra_last_login_at')->nullable();

            $table->unique(
                ['entra_tenant_id', 'entra_object_id'],
                'users_entra_identity_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique('users_entra_identity_unique');
            $table->dropColumn(['entra_tenant_id', 'entra_object_id', 'entra_user_principal_name', 'entra_last_login_at']);
        });
    }
};
