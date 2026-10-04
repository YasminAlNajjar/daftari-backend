<?php

namespace App\Services\Notifications;

use JsonException;
use RuntimeException;

class FirebaseCredentialsResolver
{
    public function resolve(): array|string
    {
        $encodedCredentials = config(
            'notifications.firebase.credentials_base64'
        );

        if (is_string($encodedCredentials) && trim($encodedCredentials) !== '') {
            return $this->resolveBase64($encodedCredentials);
        }

        $credentialsPath = config('notifications.firebase.credentials');

        if (! is_string($credentialsPath) || ! is_readable($credentialsPath)) {
            throw new RuntimeException(
                'Configure FIREBASE_CREDENTIALS_BASE64 or provide a readable FIREBASE_CREDENTIALS file.'
            );
        }

        return $credentialsPath;
    }

    private function resolveBase64(string $encodedCredentials): array
    {
        $decodedCredentials = base64_decode(
            trim($encodedCredentials),
            true
        );

        if ($decodedCredentials === false) {
            throw new RuntimeException(
                'FIREBASE_CREDENTIALS_BASE64 is not valid Base64.'
            );
        }

        try {
            $credentials = json_decode(
                $decodedCredentials,
                true,
                flags: JSON_THROW_ON_ERROR
            );
        } catch (JsonException $exception) {
            throw new RuntimeException(
                'FIREBASE_CREDENTIALS_BASE64 does not contain valid JSON.',
                previous: $exception
            );
        }

        if (! is_array($credentials)) {
            throw new RuntimeException(
                'Firebase credentials must decode to a JSON object.'
            );
        }

        foreach (
            ['type', 'project_id', 'private_key', 'client_email', 'token_uri'] as $requiredField
        ) {
            if (empty($credentials[$requiredField])) {
                throw new RuntimeException(
                    "Firebase credentials are missing {$requiredField}."
                );
            }
        }

        if ($credentials['type'] !== 'service_account') {
            throw new RuntimeException(
                'Firebase credentials must be a service account.'
            );
        }

        $configuredProjectId = config('notifications.firebase.project_id');

        if (
            is_string($configuredProjectId)
            && $configuredProjectId !== ''
            && $credentials['project_id'] !== $configuredProjectId
        ) {
            throw new RuntimeException(
                'Firebase credentials project_id does not match FIREBASE_PROJECT_ID.'
            );
        }

        return $credentials;
    }
}
