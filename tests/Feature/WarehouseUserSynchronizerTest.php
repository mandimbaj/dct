<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\WarehouseUserSynchronizer;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WarehouseUserSynchronizerTest extends TestCase
{
    use RefreshDatabase;

    private array $originalWarehouseConnection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalWarehouseConnection = config('database.connections.warehouse');
        config()->set('database.connections.warehouse', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::purge('warehouse');
        Cache::flush();

        Schema::connection('warehouse')->create('authentication_customuser', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('first_name', 30);
            $table->string('last_name', 150);
            $table->string('email', 254)->unique();
            $table->string('username', 150);
            $table->boolean('is_active');
            $table->dateTime('last_login')->nullable();
            $table->integer('location_id')->nullable();
        });
    }

    protected function tearDown(): void
    {
        DB::purge('warehouse');
        config()->set('database.connections.warehouse', $this->originalWarehouseConnection);

        parent::tearDown();
    }

    public function test_it_adds_django_users_without_granting_access_and_avoids_duplicates(): void
    {
        $existing = User::factory()->create([
            'name' => 'Existing local name',
            'email' => 'existing@example.test',
            'location_id' => null,
            'role_id' => null,
            'menu_permissions' => null,
            'is_super_admin' => false,
        ]);
        $this->insertWarehouseUser(10, 'Existing', 'Django', 'existing@example.test', 9, true);
        $this->insertWarehouseUser(11, 'Imported', 'User', 'imported@example.test', 9, false);
        $this->insertWarehouseUser(12, 'Other', 'Country', 'other@example.test', 28, true);

        $summary = app(WarehouseUserSynchronizer::class)->sync(9);

        $this->assertSame(['total' => 2, 'created' => 1, 'matched' => 1], $summary);
        $this->assertSame(2, User::query()->count());
        $this->assertSame('Existing local name', $existing->fresh()->name);

        $imported = User::query()
            ->where('email', 'imported@example.test')
            ->with('warehouseIdentity')
            ->firstOrFail();

        $this->assertSame('Imported User', $imported->name);
        $this->assertSame(9, $imported->location_id);
        $this->assertNull($imported->role_id);
        $this->assertFalse($imported->is_super_admin);
        $this->assertSame('django', $imported->identity_source);
        $this->assertFalse($imported->warehouseIdentity->is_active);

        $secondSummary = app(WarehouseUserSynchronizer::class)->sync(9);

        $this->assertSame(0, $secondSummary['created']);
        $this->assertSame(2, User::query()->count());
    }

    private function insertWarehouseUser(
        int $id,
        string $firstName,
        string $lastName,
        string $email,
        int $locationId,
        bool $isActive,
    ): void {
        DB::connection('warehouse')->table('authentication_customuser')->insert([
            'id' => $id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'username' => $email,
            'is_active' => $isActive,
            'last_login' => null,
            'location_id' => $locationId,
        ]);
    }
}
