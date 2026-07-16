<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\WarehouseUser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WarehouseUserTest extends TestCase
{
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

        Schema::connection('warehouse')->create('authentication_customuser', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('password', 128);
            $table->dateTime('last_login')->nullable();
            $table->boolean('is_superuser');
            $table->string('first_name', 30);
            $table->string('last_name', 150);
            $table->boolean('is_staff');
            $table->boolean('is_active');
            $table->dateTime('date_joined');
            $table->string('title', 45);
            $table->string('gender', 45);
            $table->string('email', 254)->unique();
            $table->string('postcode', 6)->nullable();
            $table->string('username', 150);
            $table->dateTime('date_created')->nullable();
            $table->dateTime('date_lastupdated')->nullable();
            $table->integer('location_id')->nullable();
        });
    }

    protected function tearDown(): void
    {
        DB::purge('warehouse');
        config()->set('database.connections.warehouse', $this->originalWarehouseConnection);

        parent::tearDown();
    }

    public function test_it_reuses_an_existing_django_user_by_email(): void
    {
        $this->insertWarehouseUser(139, 'cv@example.test');
        $user = new User([
            'name' => 'Cabo Verde User',
            'email' => 'CV@example.test',
            'location_id' => 9,
        ]);

        $this->assertSame(139, WarehouseUser::id($user));
        $this->assertSame(1, DB::connection('warehouse')->table('authentication_customuser')->count());
    }

    public function test_it_creates_an_inactive_warehouse_identity_for_a_laravel_only_user(): void
    {
        $user = new User([
            'name' => 'Cabo Verde User',
            'email' => 'cv2@aho.local',
            'location_id' => 9,
        ]);

        $warehouseUserId = WarehouseUser::id($user);
        $warehouseUser = DB::connection('warehouse')
            ->table('authentication_customuser')
            ->find($warehouseUserId);

        $this->assertSame('cv2@aho.local', $warehouseUser->email);
        $this->assertSame(9, $warehouseUser->location_id);
        $this->assertSame(0, $warehouseUser->is_active);
        $this->assertSame(0, $warehouseUser->is_staff);
        $this->assertSame(0, $warehouseUser->is_superuser);
        $this->assertStringStartsWith('!', $warehouseUser->password);
    }

    private function insertWarehouseUser(int $id, string $email): void
    {
        $now = now();

        DB::connection('warehouse')->table('authentication_customuser')->insert([
            'id' => $id,
            'password' => '!',
            'last_login' => null,
            'is_superuser' => false,
            'first_name' => 'Existing',
            'last_name' => 'User',
            'is_staff' => false,
            'is_active' => true,
            'date_joined' => $now,
            'title' => '',
            'gender' => '',
            'email' => $email,
            'postcode' => null,
            'username' => $email,
            'date_created' => $now,
            'date_lastupdated' => $now,
            'location_id' => 9,
        ]);
    }
}
