<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_integration_field_mappings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('data_integration_connection_id')->constrained()->cascadeOnDelete();
            $table->string('local_field')->index();
            $table->string('external_field')->index();
            $table->string('field_type')->default('direct'); // direct, computed, conditional, skip
            $table->json('transformation_config')->nullable(); // pour les transformations complexes
            $table->boolean('is_required')->default(false);
            $table->text('notes')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['data_integration_connection_id', 'local_field']);
            $table->index(['data_integration_connection_id', 'field_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_integration_field_mappings');
    }
};
