<?php

namespace Tests\Unit;

use App\Models\Role;
use App\Models\User;
use App\Support\NotificationRecipients;
use App\Support\UserPermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationRecipientsTest extends TestCase
{
    use RefreshDatabase;

    public function test_country_notifications_target_regional_and_country_administrators(): void
    {
        $countryId = 108;
        $otherCountryId = 404;

        $superAdmin = User::factory()->create(['is_super_admin' => true]);
        $regionalAdmin = User::factory()->create([
            'can_view_all_countries' => true,
            'is_super_admin' => false,
            'location_id' => null,
        ]);
        $countryAdmin = User::factory()->create([
            'is_super_admin' => false,
            'is_country_admin' => true,
            'location_id' => $countryId,
        ]);
        $countryApproverRole = Role::query()->create([
            'name' => 'Country approver',
            'location_id' => $countryId,
            'menu_permissions' => [
                UserPermissions::ACTION_VIEW => ['resource:health-indicator-value'],
                UserPermissions::ACTION_APPROVE => ['resource:health-indicator-value'],
            ],
        ]);
        $countryApprover = User::factory()->create([
            'is_super_admin' => false,
            'is_country_admin' => false,
            'location_id' => $countryId,
            'role_id' => $countryApproverRole->id,
        ]);
        $ordinaryCountryUser = User::factory()->create([
            'is_super_admin' => false,
            'is_country_admin' => false,
            'location_id' => $countryId,
            'menu_permissions' => [],
        ]);
        $otherCountryAdmin = User::factory()->create([
            'is_super_admin' => false,
            'is_country_admin' => true,
            'location_id' => $otherCountryId,
        ]);
        $actor = User::factory()->create([
            'is_super_admin' => false,
            'location_id' => $countryId,
        ]);

        $recipientIds = NotificationRecipients::forCountry($countryId, $actor->id)
            ->pluck('id')
            ->all();

        $this->assertContains($superAdmin->id, $recipientIds);
        $this->assertContains($regionalAdmin->id, $recipientIds);
        $this->assertContains($countryAdmin->id, $recipientIds);
        $this->assertContains($countryApprover->id, $recipientIds);
        $this->assertNotContains($ordinaryCountryUser->id, $recipientIds);
        $this->assertNotContains($otherCountryAdmin->id, $recipientIds);
        $this->assertNotContains($actor->id, $recipientIds);
    }

    public function test_global_notifications_target_only_regional_administrators(): void
    {
        $regionalAdmin = User::factory()->create([
            'can_view_all_countries' => true,
            'is_super_admin' => false,
            'location_id' => null,
        ]);
        $countryAdmin = User::factory()->create([
            'is_country_admin' => true,
            'location_id' => 108,
        ]);

        $recipientIds = NotificationRecipients::forCountry(null)
            ->pluck('id')
            ->all();

        $this->assertContains($regionalAdmin->id, $recipientIds);
        $this->assertNotContains($countryAdmin->id, $recipientIds);
    }
}
