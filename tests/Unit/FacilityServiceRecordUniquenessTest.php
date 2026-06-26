<?php

namespace Tests\Unit;

use App\Models\FacilityServiceCapacity;
use App\Support\FacilityServiceRecordUniqueness;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FacilityServiceRecordUniquenessTest extends TestCase
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

        Schema::connection('warehouse')->create('stg_facility_services_provision', function (Blueprint $table): void {
            $table->increments('capacity_id');
            $table->integer('facility_id');
            $table->integer('domain_id');
            $table->integer('units_id');
            $table->date('date_assessed');
        });
    }

    public function test_duplicate_capacity_record_is_rejected_before_database_insert(): void
    {
        DB::connection('warehouse')->table('stg_facility_services_provision')->insert([
            'facility_id' => 42460,
            'domain_id' => 4,
            'units_id' => 19,
            'date_assessed' => '2026-06-23 00:00:00',
        ]);

        $this->expectException(ValidationException::class);

        FacilityServiceRecordUniqueness::validateOrFail(FacilityServiceCapacity::class, [
            'facility_id' => 42460,
            'domain_id' => 4,
            'units_id' => 19,
            'date_assessed' => '2026-06-23',
        ]);
    }

    public function test_existing_record_can_be_saved_without_flagging_itself_as_duplicate(): void
    {
        DB::connection('warehouse')->table('stg_facility_services_provision')->insert([
            'capacity_id' => 1,
            'facility_id' => 42460,
            'domain_id' => 4,
            'units_id' => 19,
            'date_assessed' => '2026-06-23 00:00:00',
        ]);

        $record = FacilityServiceCapacity::query()->findOrFail(1);

        FacilityServiceRecordUniqueness::validateOrFail(FacilityServiceCapacity::class, [
            'facility_id' => 42460,
            'domain_id' => 4,
            'units_id' => 19,
            'date_assessed' => '2026-06-23',
        ], $record);

        $this->assertTrue(true);
    }

    public function test_capacity_record_with_different_unit_is_allowed(): void
    {
        DB::connection('warehouse')->table('stg_facility_services_provision')->insert([
            'facility_id' => 42460,
            'domain_id' => 4,
            'units_id' => 19,
            'date_assessed' => '2026-06-23 00:00:00',
        ]);

        FacilityServiceRecordUniqueness::validateOrFail(FacilityServiceCapacity::class, [
            'facility_id' => 42460,
            'domain_id' => 4,
            'units_id' => 20,
            'date_assessed' => '2026-06-23',
        ]);

        $this->assertTrue(true);
    }
}
