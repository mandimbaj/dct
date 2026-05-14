<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_page_visits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_name')->nullable();
            $table->string('user_email')->nullable()->index();
            $table->boolean('is_super_admin')->default(false)->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->string('country_iso', 16)->nullable()->index();
            $table->string('country_name')->nullable();
            $table->string('country_route', 16)->nullable()->index();
            $table->string('method', 12)->default('GET');
            $table->string('path', 2048);
            $table->string('full_url', 2048)->nullable();
            $table->string('route_name')->nullable();
            $table->string('page_label')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('visited_at')->index();
            $table->timestamps();

            $table->index(['user_id', 'visited_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_page_visits');
    }
};
