<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_defers_expensive_archive_aggregates(): void
    {
        $queries = $this->captureWarehouseQueries();
        $user = User::factory()->create(['is_super_admin' => true]);

        $this
            ->actingAs($user)
            ->get('/admin/af')
            ->assertOk();

        $this->assertFalse($queries->contains(
            fn (string $sql): bool => str_contains($sql, 'fact_data_archive'),
        ));
    }

    public function test_indicator_pages_do_not_preload_complete_reference_catalogues(): void
    {
        $queries = $this->captureWarehouseQueries();
        $user = User::factory()->create(['is_super_admin' => true]);

        $this
            ->actingAs($user)
            ->get('/admin/af/indicators/values')
            ->assertOk();

        $this
            ->get('/admin/af/indicators/values/create')
            ->assertOk();

        $this->assertFalse($queries->contains(
            fn (string $sql): bool => str_contains($sql, 'from `stg_indicator` limit 10000'),
        ));
    }

    /**
     * @return Collection<int, string>
     */
    private function captureWarehouseQueries(): Collection
    {
        $queries = collect();

        DB::connection('warehouse')->listen(function (QueryExecuted $query) use ($queries): void {
            $queries->push(strtolower(preg_replace('/\s+/', ' ', $query->sql) ?? $query->sql));
        });

        return $queries;
    }
}
