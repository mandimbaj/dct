<?php

namespace Tests\Feature;

use App\Models\HealthIndicatorValue;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class HealthIndicatorPriorityLimitTest extends TestCase
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

        Schema::connection('warehouse')->create('fact_data_indicators', function (Blueprint $table): void {
            $table->id('fact_id');
            $table->integer('location_id');
            $table->boolean('priority')->default(false);
        });
    }

    public function test_country_cannot_receive_more_than_ten_priority_indicator_values(): void
    {
        foreach (range(1, HealthIndicatorValue::PRIORITY_LIMIT_PER_LOCATION) as $index) {
            DB::connection('warehouse')->table('fact_data_indicators')->insert([
                'location_id' => 5,
                'priority' => true,
            ]);
        }

        $this->assertTrue(HealthIndicatorValue::priorityLimitReachedForLocation(5));

        $this->expectException(ValidationException::class);

        HealthIndicatorValue::query()->create([
            'location_id' => 5,
            'priority' => true,
        ]);
    }

    public function test_current_priority_record_is_excluded_from_its_own_country_limit(): void
    {
        foreach (range(1, HealthIndicatorValue::PRIORITY_LIMIT_PER_LOCATION - 1) as $index) {
            DB::connection('warehouse')->table('fact_data_indicators')->insert([
                'location_id' => 9,
                'priority' => true,
            ]);
        }

        $factId = DB::connection('warehouse')->table('fact_data_indicators')->insertGetId([
            'location_id' => 9,
            'priority' => true,
        ]);

        $record = HealthIndicatorValue::query()->findOrFail($factId);

        $this->assertFalse(HealthIndicatorValue::priorityLimitReachedForLocation(9, $record));
    }
}
