<?php

namespace Tests\Feature;

use App\Filament\Resources\DataIntegrationConnections\DataIntegrationConnectionResource;
use App\Models\DataIntegrationConnection;
use App\Models\User;
use App\Support\UserCountryAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataIntegrationCountryIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.warehouse' => config('database.connections.sqlite')]);
    }

    public function test_country_user_only_sees_connections_from_their_country(): void
    {
        $senegalUser = User::factory()->create(['location_id' => 38]);
        $sudanUser = User::factory()->create(['location_id' => 42]);
        $senegal = DataIntegrationConnection::query()->create($this->connectionData($senegalUser, 38, 'Senegal'));
        DataIntegrationConnection::query()->create($this->connectionData($sudanUser, 42, 'Sudan'));

        $this->actingAs($senegalUser);
        UserCountryAccess::forgetCachedLocations();

        $this->assertSame([$senegal->id], DataIntegrationConnectionResource::getEloquentQuery()->pluck('id')->all());
    }

    public function test_super_admin_sees_all_connections(): void
    {
        $senegalUser = User::factory()->create(['location_id' => 38]);
        $sudanUser = User::factory()->create(['location_id' => 42]);
        DataIntegrationConnection::query()->create($this->connectionData($senegalUser, 38, 'Senegal'));
        DataIntegrationConnection::query()->create($this->connectionData($sudanUser, 42, 'Sudan'));
        $superAdmin = User::factory()->create(['is_super_admin' => true, 'location_id' => null]);

        $this->actingAs($superAdmin);
        UserCountryAccess::forgetCachedLocations();

        $this->assertCount(2, DataIntegrationConnectionResource::getEloquentQuery()->get());
    }

    public function test_country_is_forced_when_a_country_user_creates_a_connection(): void
    {
        $senegalUser = User::factory()->create(['location_id' => 38]);
        $this->actingAs($senegalUser);
        UserCountryAccess::forgetCachedLocations();

        $connection = DataIntegrationConnection::query()->create(
            $this->connectionData($senegalUser, 42, 'Forced to Senegal'),
        );

        $this->assertSame(38, $connection->location_id);
    }

    public function test_super_admin_can_create_a_regional_connection_without_a_country(): void
    {
        $superAdmin = User::factory()->create(['is_super_admin' => true, 'location_id' => null]);
        $this->actingAs($superAdmin);
        UserCountryAccess::forgetCachedLocations();

        $connection = DataIntegrationConnection::query()->create(
            $this->connectionData($superAdmin, null, 'AFRO regional'),
        );

        $this->assertNull($connection->location_id);
    }

    /** @return array<string, mixed> */
    private function connectionData(User $user, ?int $locationId, string $name): array
    {
        return [
            'user_id' => $user->id,
            'location_id' => $locationId,
            'name' => $name,
            'provider' => DataIntegrationConnection::PROVIDER_CUSTOM,
            'integration_method' => DataIntegrationConnection::METHOD_API,
            'api_url' => 'https://example.test/api',
        ];
    }
}
