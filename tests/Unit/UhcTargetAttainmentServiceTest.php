<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\UhcClock\UhcTargetAttainmentService;
use App\Support\UserCountryAccess;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UhcTargetAttainmentServiceTest extends TestCase
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

        $this->createWarehouseTables();
        app()->setLocale('en');
        UserCountryAccess::forgetCachedLocations();
        $this->actingAs(new User([
            'is_super_admin' => true,
            'can_view_all_countries' => true,
        ]));
    }

    public function test_it_summarizes_uhc_clock_framework_progress_by_country(): void
    {
        DB::connection('warehouse')->table('stg_location')->insert([
            'location_id' => 1,
            'code' => 'CTA',
        ]);
        DB::connection('warehouse')->table('stg_location_translation')->insert([
            'master_id' => 1,
            'language_code' => 'en',
            'name' => 'Country A',
        ]);
        DB::connection('warehouse')->table('stg_uhclock_country_indicators_selection')->insert([
            'countrychoice_id' => 1,
            'location_id' => 1,
        ]);

        $this->insertGroup(1, 'Day [Health Impact and Outcome]');
        $this->insertGroup(2, 'Hour [UHC Monitoring and Financial Systems]');
        $this->insertGroup(3, 'Minute [System Performance and Service Utilization]');
        $this->insertGroup(4, 'Second [Health System Inputs]');

        $indicators = [
            [10, 12, 'AFR0012', 'impact', 1, 'Maternal mortality ratio'],
            [20, 128, 'AFR0128', 'outcome', 2, 'UHC service coverage index'],
            [30, 105, 'AFR0105', 'output', 3, 'Family planning coverage'],
            [40, 286, 'AFR0286', 'input', 4, 'Nursing and midwifery personnel density'],
            [50, 999, 'AFR0999', 'input', 4, 'Missing current value'],
        ];

        foreach ($indicators as [$uhcIndicatorId, $indicatorId, $code, $type, $groupId, $name]) {
            DB::connection('warehouse')->table('stg_indicator')->insert([
                'indicator_id' => $indicatorId,
                'afrocode' => $code,
            ]);
            DB::connection('warehouse')->table('stg_indicator_translation')->insert([
                'master_id' => $indicatorId,
                'language_code' => 'en',
                'name' => $name,
            ]);
            DB::connection('warehouse')->table('stg_uhclock_indicators')->insert([
                'id' => $uhcIndicatorId,
                'Indicator_type' => $type,
                'group_id' => $groupId,
                'indicator_id' => $indicatorId,
            ]);
            DB::connection('warehouse')->table('stg_uhclock_country_indicators_selection_indicators')->insert([
                'countryselectionuhcindicators_id' => 1,
                'stguhclockindicators_id' => $uhcIndicatorId,
            ]);
        }

        $this->insertFact(indicatorId: 12, value: 100, target: null, endPeriod: 2016);
        $this->insertFact(indicatorId: 12, value: 80, target: 50, endPeriod: 2024);
        $this->insertFact(indicatorId: 128, value: 50, target: null, endPeriod: 2016);
        $this->insertFact(indicatorId: 128, value: 60, target: null, endPeriod: 2024);
        $this->insertFact(indicatorId: 105, value: 40, target: null, endPeriod: 2016);
        $this->insertFact(indicatorId: 105, value: 55, target: null, endPeriod: 2024);
        $this->insertFact(indicatorId: 286, value: 2, target: null, endPeriod: 2016);
        $this->insertFact(indicatorId: 286, value: 3, target: null, endPeriod: 2024);
        $this->insertFact(indicatorId: 999, value: 10, target: null, endPeriod: 2016);

        $summary = app(UhcTargetAttainmentService::class)->summary();

        $this->assertSame(5, $summary['selected']);
        $this->assertSame(4, $summary['assessed']);
        $this->assertSame(0, $summary['achieved']);
        $this->assertSame(1, $summary['below_target']);
        $this->assertSame(1, $summary['not_evaluable']);
        $this->assertSame(1, $summary['target_evaluable']);
        $this->assertSame(3, $summary['no_target']);
        $this->assertEqualsWithDelta(0.0, $summary['achievement_rate'], 0.01);
        $this->assertSame('Country A', $summary['countries'][0]['country']);

        $this->assertEqualsWithDelta(30.0, $summary['levels']['day']['apc_remaining_average'], 0.01);
        $this->assertEqualsWithDelta(80.0, $summary['levels']['hour']['apc_remaining_average'], 0.01);
        $this->assertEqualsWithDelta(15.0, $summary['levels']['minute']['change_average'], 0.01);
        $this->assertEqualsWithDelta(50.0, $summary['levels']['second']['change_average'], 0.01);
    }

    public function test_it_uses_archived_indicator_values_when_active_rows_are_missing(): void
    {
        DB::connection('warehouse')->table('stg_location')->insert([
            'location_id' => 2,
            'code' => 'CTB',
        ]);
        DB::connection('warehouse')->table('stg_location_translation')->insert([
            'master_id' => 2,
            'language_code' => 'en',
            'name' => 'Country B',
        ]);
        DB::connection('warehouse')->table('stg_uhclock_country_indicators_selection')->insert([
            'countrychoice_id' => 2,
            'location_id' => 2,
        ]);

        $this->insertGroup(1, 'Day [Health Impact and Outcome]');

        DB::connection('warehouse')->table('stg_indicator')->insert([
            'indicator_id' => 777,
            'afrocode' => 'AFR0777',
        ]);
        DB::connection('warehouse')->table('stg_indicator_translation')->insert([
            'master_id' => 777,
            'language_code' => 'en',
            'name' => 'Archived UHC indicator',
        ]);
        DB::connection('warehouse')->table('stg_uhclock_indicators')->insert([
            'id' => 7770,
            'Indicator_type' => 'impact',
            'group_id' => 1,
            'indicator_id' => 777,
        ]);
        DB::connection('warehouse')->table('stg_uhclock_country_indicators_selection_indicators')->insert([
            'countryselectionuhcindicators_id' => 2,
            'stguhclockindicators_id' => 7770,
        ]);

        $this->insertFact(indicatorId: 777, value: 40, target: null, endPeriod: 2016, table: 'fact_data_archive', locationId: 2);
        $this->insertFact(indicatorId: 777, value: 60, target: null, endPeriod: 2024, table: 'fact_data_archive', locationId: 2);

        $summary = app(UhcTargetAttainmentService::class)->summary();

        $this->assertSame(1, $summary['selected']);
        $this->assertSame(1, $summary['assessed']);
        $this->assertSame(0, $summary['not_evaluable']);
        $this->assertSame('Country B', $summary['countries'][0]['country']);
        $this->assertEqualsWithDelta(50.0, $summary['levels']['day']['apc_remaining_average'], 0.01);
        $this->assertSame('archive', $summary['countries'][0]['details']['assessed_examples'][0]['baseline_source']);
        $this->assertSame('archive', $summary['countries'][0]['details']['assessed_examples'][0]['current_source']);
    }

    public function test_it_prefers_the_exact_2016_baseline_before_a_covering_interval(): void
    {
        DB::connection('warehouse')->table('stg_location')->insert([
            'location_id' => 5,
            'code' => 'CTE',
        ]);
        DB::connection('warehouse')->table('stg_location_translation')->insert([
            'master_id' => 5,
            'language_code' => 'en',
            'name' => 'Country E',
        ]);
        DB::connection('warehouse')->table('stg_uhclock_country_indicators_selection')->insert([
            'countrychoice_id' => 5,
            'location_id' => 5,
        ]);

        $this->insertGroup(1, 'Day [Health Impact and Outcome]');
        DB::connection('warehouse')->table('stg_indicator')->insert([
            'indicator_id' => 106,
            'afrocode' => 'AFR0106',
        ]);
        DB::connection('warehouse')->table('stg_indicator_translation')->insert([
            'master_id' => 106,
            'language_code' => 'en',
            'name' => 'Adolescent birth rate',
        ]);
        DB::connection('warehouse')->table('stg_uhclock_indicators')->insert([
            'id' => 1060,
            'Indicator_type' => 'impact',
            'group_id' => 1,
            'indicator_id' => 106,
        ]);
        DB::connection('warehouse')->table('stg_uhclock_country_indicators_selection_indicators')->insert([
            'countryselectionuhcindicators_id' => 5,
            'stguhclockindicators_id' => 1060,
        ]);

        $this->insertFact(indicatorId: 106, value: 96, target: null, endPeriod: 2018, locationId: 5, startPeriod: 2010, period: '2010-2018');
        $this->insertFact(indicatorId: 106, value: 80, target: null, endPeriod: 2016, locationId: 5);
        $this->insertFact(indicatorId: 106, value: 70, target: null, endPeriod: 2024, locationId: 5);

        $summary = app(UhcTargetAttainmentService::class)->summary();
        $example = $summary['countries'][0]['details']['assessed_examples'][0];

        $this->assertSame('2016', $example['baseline_period']);
        $this->assertSame(80.0, $example['baseline_value']);
    }

    public function test_it_uses_an_interval_starting_in_2016_when_exact_2016_is_missing(): void
    {
        DB::connection('warehouse')->table('stg_location')->insert([
            'location_id' => 6,
            'code' => 'CTF',
        ]);
        DB::connection('warehouse')->table('stg_location_translation')->insert([
            'master_id' => 6,
            'language_code' => 'en',
            'name' => 'Country F',
        ]);
        DB::connection('warehouse')->table('stg_uhclock_country_indicators_selection')->insert([
            'countrychoice_id' => 6,
            'location_id' => 6,
        ]);

        $this->insertGroup(1, 'Day [Health Impact and Outcome]');
        DB::connection('warehouse')->table('stg_indicator')->insert([
            'indicator_id' => 106,
            'afrocode' => 'AFR0106',
        ]);
        DB::connection('warehouse')->table('stg_indicator_translation')->insert([
            'master_id' => 106,
            'language_code' => 'en',
            'name' => 'Adolescent birth rate',
        ]);
        DB::connection('warehouse')->table('stg_uhclock_indicators')->insert([
            'id' => 1060,
            'Indicator_type' => 'impact',
            'group_id' => 1,
            'indicator_id' => 106,
        ]);
        DB::connection('warehouse')->table('stg_uhclock_country_indicators_selection_indicators')->insert([
            'countryselectionuhcindicators_id' => 6,
            'stguhclockindicators_id' => 1060,
        ]);

        $this->insertFact(indicatorId: 106, value: 96, target: null, endPeriod: 2018, locationId: 6, startPeriod: 2010, period: '2010-2018');
        $this->insertFact(indicatorId: 106, value: 88, target: null, endPeriod: 2020, locationId: 6, startPeriod: 2016, period: '2016-2020');
        $this->insertFact(indicatorId: 106, value: 70, target: null, endPeriod: 2024, locationId: 6);

        $summary = app(UhcTargetAttainmentService::class)->summary();
        $example = $summary['countries'][0]['details']['assessed_examples'][0];

        $this->assertSame('2016-2020', $example['baseline_period']);
        $this->assertSame(88.0, $example['baseline_value']);
    }

    public function test_it_prioritizes_national_data_sources_before_international_sources(): void
    {
        DB::connection('warehouse')->table('stg_location')->insert([
            'location_id' => 3,
            'code' => 'CTC',
        ]);
        DB::connection('warehouse')->table('stg_location_translation')->insert([
            'master_id' => 3,
            'language_code' => 'en',
            'name' => 'Country C',
        ]);
        DB::connection('warehouse')->table('stg_uhclock_country_indicators_selection')->insert([
            'countrychoice_id' => 3,
            'location_id' => 3,
        ]);

        $this->insertDataSource(2, 'national', 'Country-level Health Information Systems');
        $this->insertDataSource(3, 'global', 'WHO Global Health Observatory');
        $this->insertGroup(3, 'Minute [System Performance and Service Utilization]');

        DB::connection('warehouse')->table('stg_indicator')->insert([
            'indicator_id' => 105,
            'afrocode' => 'AFR0105',
        ]);
        DB::connection('warehouse')->table('stg_indicator_translation')->insert([
            'master_id' => 105,
            'language_code' => 'en',
            'name' => 'Family planning coverage',
        ]);
        DB::connection('warehouse')->table('stg_uhclock_indicators')->insert([
            'id' => 1050,
            'Indicator_type' => 'output',
            'group_id' => 3,
            'indicator_id' => 105,
        ]);
        DB::connection('warehouse')->table('stg_uhclock_country_indicators_selection_indicators')->insert([
            'countryselectionuhcindicators_id' => 3,
            'stguhclockindicators_id' => 1050,
        ]);

        $this->insertFact(indicatorId: 105, value: 40, target: null, endPeriod: 2016, locationId: 3, datasourceId: 2);
        $this->insertFact(indicatorId: 105, value: 400, target: null, endPeriod: 2016, locationId: 3, datasourceId: 3);
        $this->insertFact(indicatorId: 105, value: 60, target: null, endPeriod: 2022, locationId: 3, datasourceId: 2);
        $this->insertFact(indicatorId: 105, value: 800, target: null, endPeriod: 2024, locationId: 3, datasourceId: 3);

        $summary = app(UhcTargetAttainmentService::class)->summary();
        $example = $summary['countries'][0]['details']['assessed_examples'][0];

        $this->assertSame(1, $summary['assessed']);
        $this->assertSame(2, $example['baseline_datasource_id']);
        $this->assertSame(2, $example['current_datasource_id']);
        $this->assertSame('Country-level Health Information Systems', $example['current_datasource_name']);
        $this->assertSame('local', $example['current_datasource_category']);
        $this->assertEqualsWithDelta(20.0, $example['change'], 0.01);
    }

    public function test_it_uses_international_data_sources_when_no_national_value_exists(): void
    {
        DB::connection('warehouse')->table('stg_location')->insert([
            'location_id' => 4,
            'code' => 'CTD',
        ]);
        DB::connection('warehouse')->table('stg_location_translation')->insert([
            'master_id' => 4,
            'language_code' => 'en',
            'name' => 'Country D',
        ]);
        DB::connection('warehouse')->table('stg_uhclock_country_indicators_selection')->insert([
            'countrychoice_id' => 4,
            'location_id' => 4,
        ]);

        $this->insertDataSource(3, 'global', 'WHO Global Health Observatory');
        $this->insertGroup(3, 'Minute [System Performance and Service Utilization]');

        DB::connection('warehouse')->table('stg_indicator')->insert([
            'indicator_id' => 205,
            'afrocode' => 'AFR0205',
        ]);
        DB::connection('warehouse')->table('stg_indicator_translation')->insert([
            'master_id' => 205,
            'language_code' => 'en',
            'name' => 'International-only indicator',
        ]);
        DB::connection('warehouse')->table('stg_uhclock_indicators')->insert([
            'id' => 2050,
            'Indicator_type' => 'output',
            'group_id' => 3,
            'indicator_id' => 205,
        ]);
        DB::connection('warehouse')->table('stg_uhclock_country_indicators_selection_indicators')->insert([
            'countryselectionuhcindicators_id' => 4,
            'stguhclockindicators_id' => 2050,
        ]);

        $this->insertFact(indicatorId: 205, value: 20, target: null, endPeriod: 2016, locationId: 4, datasourceId: 3);
        $this->insertFact(indicatorId: 205, value: 35, target: null, endPeriod: 2024, locationId: 4, datasourceId: 3);

        $summary = app(UhcTargetAttainmentService::class)->summary();
        $example = $summary['countries'][0]['details']['assessed_examples'][0];

        $this->assertSame(1, $summary['assessed']);
        $this->assertSame(3, $example['baseline_datasource_id']);
        $this->assertSame(3, $example['current_datasource_id']);
        $this->assertSame('international', $example['current_datasource_category']);
        $this->assertEqualsWithDelta(15.0, $example['change'], 0.01);
    }

    public function test_it_uses_the_active_locale_for_uhc_indicator_names(): void
    {
        DB::connection('warehouse')->table('stg_location')->insert([
            'location_id' => 2,
            'code' => 'CTB',
        ]);
        DB::connection('warehouse')->table('stg_location_translation')->insert([
            'master_id' => 2,
            'language_code' => 'fr',
            'name' => 'Pays B',
        ]);
        DB::connection('warehouse')->table('stg_uhclock_country_indicators_selection')->insert([
            'countrychoice_id' => 2,
            'location_id' => 2,
        ]);

        $this->insertGroup(1, 'Day [Health Impact and Outcome]');
        DB::connection('warehouse')->table('stg_uhclock_indicator_groups_translation')->insert([
            'master_id' => 1,
            'language_code' => 'fr',
            'name' => 'Jour [Impact sanitaire]',
        ]);

        DB::connection('warehouse')->table('stg_indicator')->insert([
            'indicator_id' => 777,
            'afrocode' => 'AFR0777',
        ]);
        DB::connection('warehouse')->table('stg_indicator_translation')->insert([
            ['master_id' => 777, 'language_code' => 'en', 'name' => 'Archived UHC indicator'],
            ['master_id' => 777, 'language_code' => 'fr', 'name' => 'Indicateur UHC archivé'],
        ]);
        DB::connection('warehouse')->table('stg_uhclock_indicators')->insert([
            'id' => 7770,
            'Indicator_type' => 'impact',
            'group_id' => 1,
            'indicator_id' => 777,
        ]);
        DB::connection('warehouse')->table('stg_uhclock_country_indicators_selection_indicators')->insert([
            'countryselectionuhcindicators_id' => 2,
            'stguhclockindicators_id' => 7770,
        ]);

        $this->insertFact(indicatorId: 777, value: 40, target: null, endPeriod: 2016, table: 'fact_data_archive', locationId: 2);
        $this->insertFact(indicatorId: 777, value: 60, target: null, endPeriod: 2024, table: 'fact_data_archive', locationId: 2);

        app()->setLocale('fr');

        $summary = app(UhcTargetAttainmentService::class)->summary();
        $example = $summary['countries'][0]['details']['assessed_examples'][0];

        $this->assertSame('Pays B', $summary['countries'][0]['country']);
        $this->assertSame('Indicateur UHC archivé', $example['indicator_name']);
        $this->assertSame('Archived UHC indicator', $example['indicator_name_en']);
        $this->assertSame('Jour [Impact sanitaire]', $example['group_name']);
    }

    public function test_it_limits_country_users_to_their_own_uhc_clock_summary(): void
    {
        DB::connection('warehouse')->table('stg_location')->insert([
            ['location_id' => 1, 'code' => 'CTA'],
            ['location_id' => 2, 'code' => 'CTB'],
        ]);
        DB::connection('warehouse')->table('stg_location_translation')->insert([
            ['master_id' => 1, 'language_code' => 'en', 'name' => 'Country A'],
            ['master_id' => 2, 'language_code' => 'en', 'name' => 'Country B'],
        ]);
        DB::connection('warehouse')->table('stg_uhclock_country_indicators_selection')->insert([
            ['countrychoice_id' => 1, 'location_id' => 1],
            ['countrychoice_id' => 2, 'location_id' => 2],
        ]);

        $this->insertGroup(1, 'Day [Health Impact and Outcome]');

        DB::connection('warehouse')->table('stg_indicator')->insert([
            'indicator_id' => 12,
            'afrocode' => 'AFR0012',
        ]);
        DB::connection('warehouse')->table('stg_indicator_translation')->insert([
            'master_id' => 12,
            'language_code' => 'en',
            'name' => 'Maternal mortality ratio',
        ]);
        DB::connection('warehouse')->table('stg_uhclock_indicators')->insert([
            'id' => 10,
            'Indicator_type' => 'impact',
            'group_id' => 1,
            'indicator_id' => 12,
        ]);
        DB::connection('warehouse')->table('stg_uhclock_country_indicators_selection_indicators')->insert([
            ['countryselectionuhcindicators_id' => 1, 'stguhclockindicators_id' => 10],
            ['countryselectionuhcindicators_id' => 2, 'stguhclockindicators_id' => 10],
        ]);

        $this->insertFact(indicatorId: 12, value: 100, target: null, endPeriod: 2016, locationId: 1);
        $this->insertFact(indicatorId: 12, value: 90, target: null, endPeriod: 2024, locationId: 1);
        $this->insertFact(indicatorId: 12, value: 100, target: null, endPeriod: 2016, locationId: 2);
        $this->insertFact(indicatorId: 12, value: 70, target: null, endPeriod: 2024, locationId: 2);

        $this->actingAs(new User([
            'location_id' => 2,
            'is_super_admin' => false,
            'can_view_all_countries' => false,
        ]));
        UserCountryAccess::forgetCachedLocations();

        $summary = app(UhcTargetAttainmentService::class)->summary();

        $this->assertSame(1, $summary['selected']);
        $this->assertSame(1, $summary['assessed']);
        $this->assertCount(1, $summary['countries']);
        $this->assertSame('Country B', $summary['countries'][0]['country']);
        $this->assertSame(2, $summary['countries'][0]['location_id']);
        $this->assertEqualsWithDelta(70.0, $summary['levels']['day']['apc_remaining_average'], 0.01);
    }

    private function createWarehouseTables(): void
    {
        Schema::connection('warehouse')->create('stg_location', function (Blueprint $table): void {
            $table->integer('location_id')->primary();
            $table->string('code');
        });

        Schema::connection('warehouse')->create('stg_location_translation', function (Blueprint $table): void {
            $table->id();
            $table->integer('master_id');
            $table->string('language_code');
            $table->string('name');
        });

        Schema::connection('warehouse')->create('stg_indicator', function (Blueprint $table): void {
            $table->integer('indicator_id')->primary();
            $table->string('afrocode')->nullable();
        });

        Schema::connection('warehouse')->create('stg_indicator_translation', function (Blueprint $table): void {
            $table->id();
            $table->integer('master_id');
            $table->string('language_code');
            $table->string('name');
        });

        Schema::connection('warehouse')->create('stg_uhclock_indicator_groups', function (Blueprint $table): void {
            $table->integer('group_id')->primary();
            $table->string('code')->nullable();
        });

        Schema::connection('warehouse')->create('stg_uhclock_indicator_groups_translation', function (Blueprint $table): void {
            $table->id();
            $table->integer('master_id');
            $table->string('language_code');
            $table->string('name');
        });

        Schema::connection('warehouse')->create('stg_uhclock_country_indicators_selection', function (Blueprint $table): void {
            $table->integer('countrychoice_id')->primary();
            $table->integer('location_id');
        });

        Schema::connection('warehouse')->create('stg_uhclock_country_indicators_selection_indicators', function (Blueprint $table): void {
            $table->id();
            $table->integer('countryselectionuhcindicators_id');
            $table->integer('stguhclockindicators_id');
        });

        Schema::connection('warehouse')->create('stg_uhclock_indicators', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('Indicator_type');
            $table->integer('group_id');
            $table->integer('indicator_id');
        });

        Schema::connection('warehouse')->create('stg_datasource', function (Blueprint $table): void {
            $table->integer('datasource_id')->primary();
            $table->string('code')->nullable();
        });

        Schema::connection('warehouse')->create('stg_datasource_translation', function (Blueprint $table): void {
            $table->id();
            $table->string('language_code');
            $table->string('name');
            $table->string('shortname')->nullable();
            $table->string('description')->nullable();
            $table->string('level')->nullable();
            $table->integer('master_id');
        });

        Schema::connection('warehouse')->create('fact_data_indicators', function (Blueprint $table): void {
            $table->id('fact_id');
            $table->integer('location_id');
            $table->integer('indicator_id');
            $table->integer('datasource_id')->nullable();
            $table->decimal('value_received', 20, 3)->nullable();
            $table->decimal('target_value', 20, 3)->nullable();
            $table->integer('start_period');
            $table->integer('end_period');
            $table->string('period');
            $table->dateTime('date_lastupdated')->nullable();
        });

        Schema::connection('warehouse')->create('fact_data_archive', function (Blueprint $table): void {
            $table->id('fact_id');
            $table->string('uuid')->nullable();
            $table->integer('location_id');
            $table->integer('indicator_id');
            $table->integer('datasource_id')->nullable();
            $table->decimal('value_received', 20, 3)->nullable();
            $table->decimal('target_value', 20, 3)->nullable();
            $table->integer('start_period');
            $table->integer('end_period');
            $table->string('period');
            $table->dateTime('date_lastupdated')->nullable();
        });
    }

    private function insertGroup(int $groupId, string $name): void
    {
        DB::connection('warehouse')->table('stg_uhclock_indicator_groups')->insert([
            'group_id' => $groupId,
        ]);
        DB::connection('warehouse')->table('stg_uhclock_indicator_groups_translation')->insert([
            'master_id' => $groupId,
            'language_code' => 'en',
            'name' => $name,
        ]);
    }

    private function insertDataSource(int $dataSourceId, string $level, string $name): void
    {
        DB::connection('warehouse')->table('stg_datasource')->insert([
            'datasource_id' => $dataSourceId,
            'code' => 'ADS'.str_pad((string) $dataSourceId, 4, '0', STR_PAD_LEFT),
        ]);
        DB::connection('warehouse')->table('stg_datasource_translation')->insert([
            'master_id' => $dataSourceId,
            'language_code' => 'en',
            'name' => $name,
            'shortname' => null,
            'description' => null,
            'level' => $level,
        ]);
    }

    private function insertFact(
        int $indicatorId,
        float $value,
        ?float $target,
        int $endPeriod,
        string $table = 'fact_data_indicators',
        int $locationId = 1,
        ?int $datasourceId = null,
        ?int $startPeriod = null,
        ?string $period = null,
    ): void {
        $payload = [
            'location_id' => $locationId,
            'indicator_id' => $indicatorId,
            'value_received' => $value,
            'target_value' => $target,
            'start_period' => $startPeriod ?? $endPeriod,
            'end_period' => $endPeriod,
            'period' => $period ?? (string) $endPeriod,
            'date_lastupdated' => now()->toDateTimeString(),
        ];

        if (Schema::connection('warehouse')->hasColumn($table, 'uuid')) {
            $payload['uuid'] = "{$table}-{$locationId}-{$indicatorId}-{$endPeriod}";
        }

        if (Schema::connection('warehouse')->hasColumn($table, 'datasource_id')) {
            $payload['datasource_id'] = $datasourceId;
        }

        DB::connection('warehouse')->table($table)->insert($payload);
    }
}
