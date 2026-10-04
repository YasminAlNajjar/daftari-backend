<?php

namespace App\Services\Notifications;

use App\Contracts\PushNotificationSender;
use App\Exceptions\InvalidDeviceTokenException;
use App\Models\DeviceToken;
use App\Models\UserNotification;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FirebasePushNotificationSender implements PushNotificationSender
{
    public function __construct(
        private FirebaseCredentialsResolver $credentialsResolver
    ) {}

    public function send(
        DeviceToken $deviceToken,
        UserNotification $notification
    ): void {
        if (! config('notifications.firebase.enabled')) {
            return;
        }

        $projectId = config('notifications.firebase.project_id');

        if (! $projectId) {
            throw new RuntimeException('FIREBASE_PROJECT_ID is not configured.');
        }

        $payloadData = collect($notification->data ?? [])
            ->merge([
                'notification_id' => $notification->id,
                'type' => $notification->type->value,
            ])
            ->reject(fn ($value) => $value === null)
            ->map(fn ($value) => is_scalar($value)
                ? (string) $value
                : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR))
            ->all();

        try {
            Http::withToken($this->accessToken())
                ->acceptJson()
                ->post(
                    "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send",
                    [
                        'message' => [
                            'token' => $deviceToken->token,
                            'notification' => [
                                'title' => $notification->title,
                                'body' => $notification->message,
                            ],
                            'data' => $payloadData,
                            'android' => [
                                'priority' => 'normal',
                                'notification' => [
                                    'channel_id' => 'default',
                                    'sound' => 'default',
                                ],
                            ],
                            'apns' => [
                                'payload' => [
                                    'aps' => [
                                        'sound' => 'default',
                                    ],
                                ],
                            ],
                        ],
                    ]
                )
                ->throw();
        } catch (RequestException $exception) {
            $isUnregistered = collect(data_get(
                $exception->response?->json(),
                'error.details',
                []
            ))->contains(
                fn (array $detail) => ($detail['errorCode'] ?? null) === 'UNREGISTERED'
            );

            if ($isUnregistered) {
                throw new InvalidDeviceTokenException(
                    'The Firebase registration token is no longer valid.',
                    previous: $exception
                );
            }

            throw $exception;
        }
    }

    private function accessToken(): string
    {
        return Cache::remember(
            'firebase.messaging.access_token',
            now()->addMinutes(50),
            fn () => $this->requestAccessToken()
        );
    }

    private function requestAccessToken(): string
    {
        $credentials = new ServiceAccountCredentials(
            ['https://www.googleapis.com/auth/firebase.messaging'],
            $this->credentialsResolver->resolve()
        );

        $token = $credentials->fetchAuthToken();

        return $token['access_token']
            ?? throw new RuntimeException('Google OAuth did not return an access token.');
    }
}
