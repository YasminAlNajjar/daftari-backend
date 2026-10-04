<?php

namespace App\Contracts;

use App\Models\DeviceToken;
use App\Models\UserNotification;

interface PushNotificationSender
{
    public function send(
        DeviceToken $deviceToken,
        UserNotification $notification
    ): void;
}
