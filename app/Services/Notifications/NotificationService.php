<?php

namespace App\Services\Notifications;

use App\Enums\UserNotificationType;
use App\Jobs\SendPushNotification;
use App\Models\User;
use App\Models\UserNotification;

class NotificationService
{
    public function create(
        User $user,
        UserNotificationType $type,
        string $title,
        string $message,
        array $data = [],
        ?string $dedupeKey = null
    ): UserNotification {
        $attributes = [
            'user_id' => $user->id,
            'type' => $type,
            'dedupe_key' => $dedupeKey,
        ];

        $values = [
            'title' => $title,
            'message' => $message,
            'data' => empty($data) ? null : $data,
            'read_at' => null,
        ];

        $notification = $dedupeKey === null
            ? UserNotification::create([...$attributes, ...$values])
            : UserNotification::firstOrCreate($attributes, $values);

        if ($notification->wasRecentlyCreated) {
            $user->deviceTokens()
                ->pluck('id')
                ->each(fn (int $deviceTokenId) =>
                    SendPushNotification::dispatch(
                        $notification->id,
                        $deviceTokenId
                    )->afterCommit()
                );
        }

        return $notification;
    }
}
