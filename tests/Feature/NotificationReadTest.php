<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\MessageReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationReadTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_open_and_delete_own_notification_after_reading(): void
    {
        $user = User::factory()->create([
            'is_super_admin' => true,
        ]);

        Notification::send($user, new MessageReceived('Test message', 'Message body', 'global'));

        $notification = $user->notifications()->firstOrFail();

        $this
            ->actingAs($user)
            ->get(route('admin.notifications.show', ['country' => 'global', 'notification' => $notification]))
            ->assertOk()
            ->assertSee('Test message')
            ->assertSee('Message body');

        $this->assertNull($notification->fresh());
    }

    public function test_user_cannot_open_another_users_notification(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        Notification::send($owner, new MessageReceived('Private message', 'Message body', 'global'));

        $notification = $owner->notifications()->firstOrFail();

        $this
            ->actingAs($otherUser)
            ->get(route('admin.notifications.show', ['country' => 'global', 'notification' => $notification]))
            ->assertNotFound();
    }
}
