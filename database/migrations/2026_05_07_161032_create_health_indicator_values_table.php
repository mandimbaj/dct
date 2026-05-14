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
        Schema::create('health_indicator_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->restrictOnDelete();
            $table->foreignId('indicator_id')->constrained()->restrictOnDelete();
            $table->foreignId('data_source_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('year');
            $table->string('period')->nullable();
            $table->string('sex')->nullable();
            $table->string('age_group')->nullable();
            $table->decimal('value', 20, 6);
            $table->string('unit_override')->nullable();
            $table->decimal('lower_bound', 20, 6)->nullable();
            $table->decimal('upper_bound', 20, 6)->nullable();
            $table->text('comment')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['country_id', 'indicator_id', 'year']);
            $table->unique([
                'country_id',
                'indicator_id',
                'data_source_id',
                'year',
                'period',
                'sex',
                'age_group',
            ], 'health_values_unique_slice');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('health_indicator_values');
    }
};
