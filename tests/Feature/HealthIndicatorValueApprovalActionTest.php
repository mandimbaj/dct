<?php

namespace Tests\Feature;

use App\Filament\Resources\HealthIndicatorValues\Pages\EditHealthIndicatorValue;
use App\Models\User;
use App\Support\ApprovalWorkflow;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class HealthIndicatorValueApprovalActionTest extends TestCase
{
    use RefreshDatabase;

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

        $this->createLookupTables();

        Schema::connection('warehouse')->create('fact_data_indicators', function (Blueprint $table): void {
            $table->id('fact_id');
            $table->integer('indicator_id')->nullable();
            $table->integer('location_id')->nullable();
            $table->string('start_period')->nullable();
            $table->string('end_period')->nullable();
            $table->string('period')->nullable();
            $table->integer('categoryoption_id')->nullable();
            $table->integer('datasource_id')->nullable();
            $table->integer('measuremethod_id')->nullable();
            $table->decimal('numerator_value', 18, 2)->nullable();
            $table->decimal('denominator_value', 18, 2)->nullable();
            $table->decimal('value_received', 18, 2)->nullable();
            $table->decimal('min_value', 18, 2)->nullable();
            $table->decimal('max_value', 18, 2)->nullable();
            $table->decimal('target_value', 18, 2)->nullable();
            $table->string('string_value')->nullable();
            $table->string(ApprovalWorkflow::STATUS_COLUMN, 30)->default(ApprovalWorkflow::STATUS_PENDING);
            $table->boolean('priority')->default(false);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string(ApprovalWorkflow::MIRROR_COLUMN, 30)->default(ApprovalWorkflow::STATUS_PENDING);
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_lastupdated')->nullable();
        });

        $this->seedLookupTables();
    }

    public function test_edit_page_approve_action_updates_form_approval_status(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);
        $now = now();

        $factId = DB::connection('warehouse')->table('fact_data_indicators')->insertGetId([
            'indicator_id' => 1,
            'location_id' => 1,
            'start_period' => '2024',
            'end_period' => '2024',
            'period' => '2024',
            'categoryoption_id' => 1,
            'datasource_id' => 1,
            'measuremethod_id' => 1,
            'value_received' => 42,
            ApprovalWorkflow::STATUS_COLUMN => ApprovalWorkflow::STATUS_PENDING,
            ApprovalWorkflow::MIRROR_COLUMN => ApprovalWorkflow::STATUS_PENDING,
            'date_created' => $now,
            'date_lastupdated' => $now,
        ]);

        $this->actingAs($user);
        URL::defaults(['country' => 'af']);

        Livewire::test(EditHealthIndicatorValue::class, ['record' => $factId])
            ->assertFormSet([ApprovalWorkflow::STATUS_COLUMN => ApprovalWorkflow::STATUS_PENDING])
            ->callAction('approve')
            ->assertFormSet([ApprovalWorkflow::STATUS_COLUMN => ApprovalWorkflow::STATUS_APPROVED]);

        $this->assertDatabaseHas('fact_data_indicators', [
            'fact_id' => $factId,
            ApprovalWorkflow::STATUS_COLUMN => ApprovalWorkflow::STATUS_APPROVED,
            ApprovalWorkflow::MIRROR_COLUMN => ApprovalWorkflow::STATUS_APPROVED,
            'approved_by' => $user->id,
        ], 'warehouse');
    }

    private function createLookupTables(): void
    {
        Schema::connection('warehouse')->create('stg_indicator', function (Blueprint $table): void {
            $table->id('indicator_id');
            $table->string('afrocode')->nullable();
            $table->string('gen_code')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_lastupdated')->nullable();
        });

        Schema::connection('warehouse')->create('stg_indicator_translation', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('master_id');
            $table->string('language_code');
            $table->string('name')->nullable();
            $table->string('shortname')->nullable();
            $table->text('definition')->nullable();
        });

        Schema::connection('warehouse')->create('stg_location', function (Blueprint $table): void {
            $table->id('location_id');
            $table->string('code')->nullable();
            $table->string('iso_alpha')->nullable();
            $table->string('iso_number')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_lastupdated')->nullable();
        });

        Schema::connection('warehouse')->create('stg_location_translation', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('master_id');
            $table->string('language_code');
            $table->string('name')->nullable();
        });

        Schema::connection('warehouse')->create('stg_category_parent', function (Blueprint $table): void {
            $table->id('category_id');
            $table->string('code')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_lastupdated')->nullable();
        });

        Schema::connection('warehouse')->create('stg_category_parent_translation', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('master_id');
            $table->string('language_code');
            $table->string('name')->nullable();
        });

        Schema::connection('warehouse')->create('stg_categoryoption', function (Blueprint $table): void {
            $table->id('categoryoption_id');
            $table->string('code')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_lastupdated')->nullable();
        });

        Schema::connection('warehouse')->create('stg_categoryoption_translation', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('master_id');
            $table->string('language_code');
            $table->string('name')->nullable();
            $table->string('shortname')->nullable();
        });

        Schema::connection('warehouse')->create('stg_datasource', function (Blueprint $table): void {
            $table->id('datasource_id');
            $table->string('code')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_lastupdated')->nullable();
        });

        Schema::connection('warehouse')->create('stg_datasource_translation', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('master_id');
            $table->string('language_code');
            $table->string('name')->nullable();
            $table->string('shortname')->nullable();
        });

        Schema::connection('warehouse')->create('stg_measuremethod', function (Blueprint $table): void {
            $table->id('measuremethod_id');
            $table->string('code')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_lastupdated')->nullable();
        });

        Schema::connection('warehouse')->create('stg_measuremethod_translation', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('master_id');
            $table->string('language_code');
            $table->string('name')->nullable();
        });
    }

    private function seedLookupTables(): void
    {
        $warehouse = DB::connection('warehouse');
        $now = now();

        $warehouse->table('stg_indicator')->insert([
            'indicator_id' => 1,
            'afrocode' => 'AFR0001',
            'gen_code' => 'GEN0001',
            'date_created' => $now,
            'date_lastupdated' => $now,
        ]);
        $warehouse->table('stg_indicator_translation')->insert([
            'master_id' => 1,
            'language_code' => 'en',
            'name' => 'Test indicator',
        ]);

        $warehouse->table('stg_location')->insert([
            'location_id' => 1,
            'code' => 'BI',
            'iso_alpha' => 'BI',
            'iso_number' => '108',
            'date_created' => $now,
            'date_lastupdated' => $now,
        ]);
        $warehouse->table('stg_location_translation')->insert([
            'master_id' => 1,
            'language_code' => 'en',
            'name' => 'Burundi',
        ]);

        $warehouse->table('stg_category_parent')->insert([
            'category_id' => 1,
            'code' => 'SEX',
            'date_created' => $now,
            'date_lastupdated' => $now,
        ]);
        $warehouse->table('stg_category_parent_translation')->insert([
            'master_id' => 1,
            'language_code' => 'en',
            'name' => 'Sex',
        ]);
        $warehouse->table('stg_categoryoption')->insert([
            'categoryoption_id' => 1,
            'category_id' => 1,
            'code' => 'TOTAL',
            'date_created' => $now,
            'date_lastupdated' => $now,
        ]);
        $warehouse->table('stg_categoryoption_translation')->insert([
            'master_id' => 1,
            'language_code' => 'en',
            'name' => 'Total',
        ]);

        $warehouse->table('stg_datasource')->insert([
            'datasource_id' => 1,
            'code' => 'NAT',
            'date_created' => $now,
            'date_lastupdated' => $now,
        ]);
        $warehouse->table('stg_datasource_translation')->insert([
            'master_id' => 1,
            'language_code' => 'en',
            'name' => 'National source',
        ]);

        $warehouse->table('stg_measuremethod')->insert([
            'measuremethod_id' => 1,
            'code' => 'COUNT',
            'date_created' => $now,
            'date_lastupdated' => $now,
        ]);
        $warehouse->table('stg_measuremethod_translation')->insert([
            'master_id' => 1,
            'language_code' => 'en',
            'name' => 'Count',
        ]);
    }
}
