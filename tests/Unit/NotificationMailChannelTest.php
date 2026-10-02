<?php

namespace Tests\Unit;

use App\Models\User;
use App\Notifications\MessageReceived;
use App\Notifications\SystemNotification;
use Tests\TestCase;

class NotificationMailChannelTest extends TestCase
{
    public function test_messages_use_database_and_mail_channels_when_email_notifications_are_enabled(): void
    {
        config(['aho.notifications.mail_enabled' => true]);

        $channels = (new MessageReceived('Message', 'Body', 'af'))->via(new User);

        $this->assertSame(['database', 'mail'], $channels);
    }

    public function test_system_notifications_use_database_and_mail_channels_when_email_notifications_are_enabled(): void
    {
        config(['aho.notifications.mail_enabled' => true]);

        $channels = (new SystemNotification('Notification', 'Body', 'af'))->via(new User);

        $this->assertSame(['database', 'mail'], $channels);
    }

    public function test_email_channel_can_be_disabled_by_environment_configuration(): void
    {
        config(['aho.notifications.mail_enabled' => false]);

        $channels = (new MessageReceived('Message', 'Body', 'af'))->via(new User);

        $this->assertSame(['database'], $channels);
    }
}
