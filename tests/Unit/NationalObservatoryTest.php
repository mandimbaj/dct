<?php

namespace Tests\Unit;

use App\Models\NationalObservatory;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class NationalObservatoryTest extends TestCase
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

        Schema::connection('warehouse')->create('stg_location_codes', function (Blueprint $table): void {
            $table->integer('location_id')->primary();
            $table->string('country_code', 15);
        });

        Schema::connection('warehouse')->create('stg_national_observatory', function (Blueprint $table): void {
            $table->increments('observatory_id');
            $table->string('uuid', 36);
            $table->string('code', 45);
            $table->string('email', 250)->nullable();
            $table->string('phone_code', 5);
            $table->string('phone_part', 15);
            $table->string('phone_number', 20)->nullable();
            $table->string('url', 2083)->nullable();
            $table->dateTime('date_created')->nullable();
            $table->dateTime('date_lastupdated')->nullable();
            $table->integer('location_id');
            $table->integer('user_id');
        });

        DB::connection('warehouse')->table('stg_location_codes')->insert([
            'location_id' => 2,
            'country_code' => '+213',
        ]);
    }

    public function test_national_observatory_generates_identity_and_phone_number_like_django_model(): void
    {
        $user = new User;
        $user->forceFill([
            'id' => 14,
            'location_id' => 2,
            'is_super_admin' => false,
            'can_view_all_countries' => false,
        ]);

        $this->actingAs($user);

        $observatory = NationalObservatory::query()->create([
            'location_id' => 2,
            'email' => 'nho@example.org',
            'phone_part' => '788888888',
            'url' => 'https://example.org',
        ]);

        $this->assertNotEmpty($observatory->uuid);
        $this->assertStringStartsWith('NHO-', $observatory->code);
        $this->assertSame(14, $observatory->user_id);
        $this->assertSame('+213', $observatory->phone_code);
        $this->assertSame('+213788888888', $observatory->phone_number);
    }

    public function test_country_can_have_only_one_national_observatory(): void
    {
        NationalObservatory::query()->create([
            'location_id' => 2,
            'email' => 'first@example.org',
            'phone_part' => '788888888',
            'url' => 'https://first.example.org',
        ]);

        $this->expectException(ValidationException::class);

        NationalObservatory::query()->create([
            'location_id' => 2,
            'email' => 'second@example.org',
            'phone_part' => '799999999',
            'url' => 'https://second.example.org',
        ]);
    }

    public function test_existing_national_observatory_can_still_be_updated(): void
    {
        $observatory = NationalObservatory::query()->create([
            'location_id' => 2,
            'email' => 'edit@example.org',
            'phone_part' => '788888888',
            'url' => 'https://before.example.org',
        ]);

        $observatory->url = 'https://after.example.org';
        $observatory->save();

        $this->assertSame('https://after.example.org', $observatory->refresh()->url);
    }

    public function test_country_user_can_update_and_delete_own_country_national_observatory(): void
    {
        $user = new User;
        $user->forceFill([
            'id' => 14,
            'location_id' => 2,
            'is_super_admin' => false,
            'can_view_all_countries' => false,
        ]);

        $this->actingAs($user);

        $observatory = NationalObservatory::query()->create([
            'location_id' => 2,
            'email' => 'country-admin@example.org',
            'phone_part' => '788888888',
            'url' => 'https://country-admin.example.org',
        ]);

        $this->assertTrue(Gate::allows('update', $observatory));
        $this->assertTrue(Gate::allows('delete', $observatory));
    }
}
