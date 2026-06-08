<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ArchiveApprovedIndicatorValuesTest extends TestCase
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

        $this->createIndicatorTables();
    }

    public function test_it_archives_approved_values_and_keeps_only_recent_active_rows(): void
    {
        $old = now()->subDays(91)->toDateTimeString();
        $recent = now()->subDays(10)->toDateTimeString();

        $this->insertActiveValue('old-approved', 'approved', $old);
        $this->insertActiveValue('recent-approved', 'approved', $recent);
        $this->insertActiveValue('old-pending', 'pending', $old);
        $this->insertActiveValue('old-approved-already-archived', 'approved', $old);
        $this->insertArchivedValue('old-approved-already-archived', $old);

        $exitCode = Artisan::call('indicators:archive-approved');

        $this->assertSame(0, $exitCode);
        $this->assertSame(3, DB::connection('warehouse')->table('fact_data_archive')->count());
        $this->assertSame(2, DB::connection('warehouse')->table('fact_data_indicators')->count());
        $this->assertDatabaseHas('fact_data_archive', ['uuid' => 'old-approved'], 'warehouse');
        $this->assertDatabaseHas('fact_data_archive', ['uuid' => 'recent-approved'], 'warehouse');
        $this->assertDatabaseMissing('fact_data_indicators', ['uuid' => 'old-approved'], 'warehouse');
        $this->assertDatabaseMissing('fact_data_indicators', ['uuid' => 'old-approved-already-archived'], 'warehouse');
        $this->assertDatabaseHas('fact_data_indicators', ['uuid' => 'recent-approved'], 'warehouse');
        $this->assertDatabaseHas('fact_data_indicators', ['uuid' => 'old-pending'], 'warehouse');
    }

    private function createIndicatorTables(): void
    {
        foreach (['fact_data_indicators', 'fact_data_archive'] as $table) {
            Schema::connection('warehouse')->create($table, function (Blueprint $table): void {
                $table->id('fact_id');
                $table->string('uuid')->nullable();
                $table->integer('indicator_id');
                $table->integer('location_id');
                $table->integer('categoryoption_id')->nullable();
                $table->integer('datasource_id')->nullable();
                $table->integer('measuremethod_id')->nullable();
                $table->decimal('numerator_value', 20, 3)->nullable();
                $table->decimal('denominator_value', 20, 3)->nullable();
                $table->decimal('value_received', 20, 3)->nullable();
                $table->decimal('min_value', 20, 3)->nullable();
                $table->decimal('max_value', 20, 3)->nullable();
                $table->decimal('target_value', 20, 3)->nullable();
                $table->string('string_value')->nullable();
                $table->integer('start_period');
                $table->integer('end_period');
                $table->string('period');
                $table->string('comment');
                $table->integer('user_id')->nullable();
                $table->dateTime('date_created')->nullable();
                $table->dateTime('date_lastupdated')->nullable();
            });
        }
    }

    private function insertActiveValue(string $uuid, string $status, string $createdAt): void
    {
        DB::connection('warehouse')->table('fact_data_indicators')->insert($this->valuePayload($uuid, $status, $createdAt));
    }

    private function insertArchivedValue(string $uuid, string $createdAt): void
    {
        DB::connection('warehouse')->table('fact_data_archive')->insert($this->valuePayload($uuid, 'approved', $createdAt));
    }

    /**
     * @return array<string, mixed>
     */
    private function valuePayload(string $uuid, string $status, string $createdAt): array
    {
        return [
            'uuid' => $uuid,
            'indicator_id' => 1,
            'location_id' => 2,
            'categoryoption_id' => 999,
            'datasource_id' => 1,
            'measuremethod_id' => 1,
            'numerator_value' => null,
            'denominator_value' => null,
            'value_received' => 42,
            'min_value' => null,
            'max_value' => null,
            'target_value' => null,
            'string_value' => null,
            'start_period' => 2024,
            'end_period' => 2024,
            'period' => '2024',
            'comment' => $status,
            'user_id' => 1,
            'date_created' => $createdAt,
            'date_lastupdated' => $createdAt,
        ];
    }
}
