<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\DashboardIndicatorValues;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DashboardIndicatorValuesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.connections.warehouse', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);

        DB::purge('warehouse');
        Schema::connection('warehouse')->create('stg_location', function (Blueprint $table): void {
            $table->integer('location_id')->primary();
            $table->integer('parent_id')->nullable();
        });
        DB::connection('warehouse')->table('stg_location')->insert([
            'location_id' => 42,
            'parent_id' => null,
        ]);

        $this->actingAs(new User([
            'location_id' => 42,
            'is_super_admin' => false,
            'can_view_all_countries' => false,
        ]));

        foreach (['fact_data_indicators', 'fact_data_archive'] as $table) {
            Schema::connection('warehouse')->create($table, function (Blueprint $table): void {
                $table->id('fact_id');
                $table->string('uuid')->nullable();
                $table->integer('indicator_id');
                $table->integer('location_id');
                $table->integer('datasource_id')->nullable();
                $table->string('comment')->nullable();
            });
        }
    }

    public function test_dashboard_rankings_combine_active_and_archived_values(): void
    {
        DB::connection('warehouse')->table('fact_data_indicators')->insert([
            'uuid' => 'active-and-archived',
            'indicator_id' => 10,
            'location_id' => 42,
            'datasource_id' => 3,
            'comment' => 'pending',
        ]);

        DB::connection('warehouse')->table('fact_data_archive')->insert([
            ['uuid' => 'active-and-archived', 'indicator_id' => 10, 'location_id' => 42, 'datasource_id' => 3, 'comment' => 'approved'],
            ['uuid' => 'archive-only-same-source', 'indicator_id' => 10, 'location_id' => 42, 'datasource_id' => 3, 'comment' => 'approved'],
            ['uuid' => 'archive-only-second-source', 'indicator_id' => 20, 'location_id' => 42, 'datasource_id' => 4, 'comment' => 'approved'],
            ['uuid' => 'archive-only-third-source', 'indicator_id' => 30, 'location_id' => 42, 'datasource_id' => 5, 'comment' => 'approved'],
        ]);

        $sources = DashboardIndicatorValues::groupedCountsWithArchiveFallback('datasource_id');
        $indicators = DashboardIndicatorValues::groupedCountsWithArchiveFallback('indicator_id');

        $this->assertSame([3 => 2, 4 => 1, 5 => 1], $sources->mapWithKeys(fn ($row): array => [(int) $row->datasource_id => (int) $row->total])->all());
        $this->assertSame([10 => 2, 20 => 1, 30 => 1], $indicators->mapWithKeys(fn ($row): array => [(int) $row->indicator_id => (int) $row->total])->all());
    }
}
