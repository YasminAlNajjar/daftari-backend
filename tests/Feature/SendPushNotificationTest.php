<?php

namespace Tests\Feature;

use App\Contracts\PushNotificationSender;
use App\Enums\UserNotificationType;
use App\Jobs\SendPushNotification;
use App\Models\DeviceToken;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SendPushNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_does_not_send_when_the_device_now_belongs_to_another_user(): void
    {
        $notificationOwner = $this->createUser(
            'notification-owner@example.com',
            '0590000011'
        );
        $deviceOwner = $this->createUser(
            'device-owner@example.com',
            '0590000012'
        );

        $notification = $this->createNotification($notificationOwner);
        $deviceToken = $this->createDeviceToken($deviceOwner);
        $sender = new RecordingPushNotificationSender;

        (new SendPushNotification(
            $notification->id,
            $deviceToken->id
        ))->handle($sender);

        $this->assertSame(0, $sender->sendCount);
    }

    public function test_it_sends_when_the_notification_and_device_belong_to_the_same_user(): void
    {
        $user = $this->createUser('owner@example.com', '0590000013');
        $notification = $this->createNotification($user);
        $deviceToken = $this->createDeviceToken($user);
        $sender = new RecordingPushNotificationSender;

        (new SendPushNotification(
            $notification->id,
            $deviceToken->id
        ))->handle($sender);

        $this->assertSame(1, $sender->sendCount);
    }

    private function createUser(string $email, string $phone): User
    {
        return User::create([
            'name' => 'Test User',
            'phone' => $phone,
            'email' => $email,
            'address' => 'Test Address',
            'business_activity_type' => 'retail',
        ]);
    }

    private function createNotification(User $user): UserNotification
    {
        return UserNotification::create([
            'user_id' => $user->id,
            'type' => UserNotificationType::SYSTEM,
            'title' => 'Test notification',
            'message' => 'Test message',
        ]);
    }

    private function createDeviceToken(User $user): DeviceToken
    {
        return DeviceToken::create([
            'user_id' => $user->id,
            'token' => 'test-fcm-token-'.$user->id,
            'platform' => 'android',
        ]);
    }
}

class RecordingPushNotificationSender implements PushNotificationSender
{
    public int $sendCount = 0;

    public function send(
        DeviceToken $deviceToken,
        UserNotification $notification
    ): void {
        $this->sendCount++;
    }
}
