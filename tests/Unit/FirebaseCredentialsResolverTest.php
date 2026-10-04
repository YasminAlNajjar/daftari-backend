<?php

namespace Tests\Unit;

use App\Services\Notifications\FirebaseCredentialsResolver;
use RuntimeException;
use Tests\TestCase;

class FirebaseCredentialsResolverTest extends TestCase
{
    public function test_it_resolves_base64_service_account_credentials(): void
    {
        config()->set('notifications.firebase.project_id', 'sanad-test');
        config()->set(
            'notifications.firebase.credentials_base64',
            base64_encode(json_encode($this->credentials(), JSON_THROW_ON_ERROR))
        );
        config()->set(
            'notifications.firebase.credentials',
            'an-unreadable-fallback-path.json'
        );

        $resolved = app(FirebaseCredentialsResolver::class)->resolve();

        $this->assertIsArray($resolved);
        $this->assertSame('sanad-test', $resolved['project_id']);
    }

    public function test_it_rejects_invalid_base64(): void
    {
        config()->set(
            'notifications.firebase.credentials_base64',
            'this is not base64!'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not valid Base64');

        app(FirebaseCredentialsResolver::class)->resolve();
    }

    public function test_it_rejects_a_different_project_id(): void
    {
        config()->set('notifications.firebase.project_id', 'expected-project');
        config()->set(
            'notifications.firebase.credentials_base64',
            base64_encode(json_encode($this->credentials(), JSON_THROW_ON_ERROR))
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('does not match FIREBASE_PROJECT_ID');

        app(FirebaseCredentialsResolver::class)->resolve();
    }

    private function credentials(): array
    {
        return [
            'type' => 'service_account',
            'project_id' => 'sanad-test',
            'private_key' => 'test-private-key',
            'client_email' => 'firebase@example.test',
            'token_uri' => 'https://oauth2.googleapis.com/token',
        ];
    }
}
