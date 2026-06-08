<?php

namespace Tests\Feature;

use App\Filament\Resources\Permissions\PermissionResource;
use App\Filament\Resources\UserPageVisits\UserPageVisitResource;
use App\Models\Role;
use App\Models\User;
use App\Support\UserCountryAccess;
use App\Support\UserPermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegionalUserAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_central_user_can_use_regional_data_without_super_admin_permissions(): void
    {
        $regionalUser = User::factory()->create([
            'is_super_admin' => false,
            'can_view_all_countries' => true,
            'location_id' => null,
            'menu_permissions' => null,
        ]);

        User::factory()->create([
            'is_super_admin' => false,
            'can_view_all_countries' => false,
            'location_id' => 101,
        ]);

        User::factory()->create([
            'is_super_admin' => false,
            'can_view_all_countries' => false,
            'location_id' => 202,
        ]);

        $this->actingAs($regionalUser);
        UserCountryAccess::forgetCachedLocations();

        $this->assertFalse($regionalUser->is_super_admin);
        $this->assertTrue($regionalUser->canViewAllCountries());
        $this->assertTrue(UserCountryAccess::canViewRegionalDashboard());
        $this->assertSame(3, UserCountryAccess::scope(User::query())->count());
        $this->assertSame([], $regionalUser->effectivePermissions()[UserPermissions::ACTION_VIEW]);
    }

    public function test_regional_data_access_does_not_open_super_admin_authentication_screens(): void
    {
        $regionalUser = User::factory()->create([
            'is_super_admin' => false,
            'can_view_all_countries' => true,
            'location_id' => null,
        ]);

        $globalRole = Role::query()->create([
            'name' => 'Regional data user',
            'location_id' => null,
            'menu_permissions' => [],
        ]);

        $this->actingAs($regionalUser);

        $this->assertFalse(PermissionResource::canAccess());
        $this->assertFalse(UserPageVisitResource::canAccess());
        $this->assertFalse($regionalUser->canAssignRole($globalRole));
    }
}
