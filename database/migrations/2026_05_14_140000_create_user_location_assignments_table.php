<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_location_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('country_location_id')->index();
            $table->unsignedBigInteger('location_id')->index();
            $table->unsignedBigInteger('locationlevel_id')->nullable()->index();
            $table->timestamps();

            $table->unique(['user_id', 'location_id']);
        });

        $this->backfillExistingUsers();
    }

    public function down(): void
    {
        Schema::dropIfExists('user_location_assignments');
    }

    private function backfillExistingUsers(): void
    {
        try {
            $users = DB::table('users')
                ->whereNotNull('location_id')
                ->where('is_super_admin', false)
                ->get(['id', 'location_id']);

            if ($users->isEmpty()) {
                return;
            }

            $children = DB::connection('warehouse')
                ->table('stg_location')
                ->whereIn('parent_id', $users->pluck('location_id')->unique()->values())
                ->get(['location_id', 'parent_id', 'locationlevel_id'])
                ->groupBy('parent_id');

            $now = now();
            $rows = [];

            foreach ($users as $user) {
                foreach ($children->get($user->location_id, collect()) as $location) {
                    $rows[] = [
                        'user_id' => $user->id,
                        'country_location_id' => $user->location_id,
                        'location_id' => $location->location_id,
                        'locationlevel_id' => $location->locationlevel_id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('user_location_assignments')->insertOrIgnore($chunk);
            }
        } catch (\Throwable) {
            // The warehouse connection can be unavailable during test setup.
            // New assignments are synchronized again when users are saved.
        }
    }
};
