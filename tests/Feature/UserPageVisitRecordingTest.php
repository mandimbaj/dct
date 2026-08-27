<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPageVisitRecordingTest extends TestCase
{
    use RefreshDatabase;

    public function test_spa_navigation_records_user_history(): void
    {
        $user = User::factory()->create([
            'email' => 'history@example.test',
            'is_super_admin' => true,
            'name' => 'History User',
        ]);

        $this
            ->actingAs($user)
            ->postJson('/user-history/record', [
                'path' => '/admin/bi/indicators/values',
            ])
            ->assertOk()
            ->assertJson(['recorded' => true]);

        $this->assertDatabaseHas('user_page_visits', [
            'user_id' => $user->id,
            'user_name' => 'History User',
            'user_email' => 'history@example.test',
            'country_iso' => 'BI',
            'country_route' => 'bi',
            'path' => 'admin/bi/indicators/values',
            'page_label' => 'Indicators / Values',
        ]);
    }

    public function test_recent_duplicate_spa_navigation_is_not_recorded_twice(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);

        $this->actingAs($user);

        $payload = ['path' => '/admin/af/authentication/users'];

        $this->postJson('/user-history/record', $payload)
            ->assertOk()
            ->assertJson(['recorded' => true]);

        $this->postJson('/user-history/record', $payload)
            ->assertOk()
            ->assertJson(['recorded' => false]);

        $this->assertDatabaseCount('user_page_visits', 1);
    }
}
