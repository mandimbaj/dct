<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthenticationMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_open_users_page(): void
    {
        $user = User::factory()->create([
            'is_super_admin' => true,
        ]);

        $this
            ->actingAs($user)
            ->get('/admin/global/authentication/users')
            ->assertOk();
    }

    public function test_super_admin_can_open_permissions_page(): void
    {
        $user = User::factory()->create([
            'is_super_admin' => true,
        ]);

        $this
            ->actingAs($user)
            ->get('/admin/global/authentication/permissions')
            ->assertOk();
    }
}
