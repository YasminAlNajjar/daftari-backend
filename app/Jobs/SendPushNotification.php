<?php

namespace App\Jobs;

use App\Contracts\PushNotificationSender;
use App\Exceptions\InvalidDeviceTokenException;
use App\Models\DeviceToken;
use App\Models\UserNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendPushNotification implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 4;

    public function __construct(
        public int $notificationId,
        public int $deviceTokenId
    ) {}

    public function backoff(): array
    {
        return [30, 120, 600];
    }

    public function handle(PushNotificationSender $sender): void
    {
        $notification = UserNotification::find($this->notificationId);
        $deviceToken = DeviceToken::find($this->deviceTokenId);

        if (
            ! $notification
            || ! $deviceToken
            || (int) $notification->user_id !== (int) $deviceToken->user_id
        ) {
            return;
        }

        try {
            $sender->send($deviceToken, $notification);
        } catch (InvalidDeviceTokenException) {
            $deviceToken->delete();
        }
    }
}
