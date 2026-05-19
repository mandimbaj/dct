<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_integration_connections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('provider')->default('custom');
            $table->string('integration_method')->default('api');
            $table->string('status')->default('draft');
            $table->string('sync_frequency')->default('manual');
            $table->string('server_name')->nullable();
            $table->unsignedInteger('port')->nullable();
            $table->string('database_driver')->nullable();
            $table->string('database_name')->nullable();
            $table->string('username')->nullable();
            $table->text('password')->nullable();
            $table->string('api_url')->nullable();
            $table->string('auth_type')->default('none');
            $table->text('api_token')->nullable();
            $table->string('api_key_name')->nullable();
            $table->text('api_key_value')->nullable();
            $table->string('client_id')->nullable();
            $table->text('client_secret')->nullable();
            $table->json('data_scope')->nullable();
            $table->json('field_mapping')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('last_tested_at')->nullable();
            $table->string('last_test_status')->nullable();
            $table->text('last_test_message')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['provider', 'integration_method']);
            $table->index(['status', 'sync_frequency']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_integration_connections');
    }
};
