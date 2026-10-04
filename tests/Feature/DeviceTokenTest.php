<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeviceTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authenticated_user_can_register_refresh_and_remove_a_device(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'phone' => '0590000000',
            'email' => 'test@example.com',
            'address' => 'Test Address',
            'business_activity_type' => 'retail',
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/device-tokens', [
            'token' => 'first-fcm-token',
            'platform' => 'android',
            'installation_id' => 'installation-1',
            'device_name' => 'Test phone',
        ])->assertOk();

        $this->postJson('/api/device-tokens', [
            'token' => 'refreshed-fcm-token',
            'platform' => 'android',
            'installation_id' => 'installation-1',
            'device_name' => 'Test phone',
        ])->assertOk();

        $this->assertDatabaseCount('device_tokens', 1);
        $this->assertDatabaseHas('device_tokens', [
            'user_id' => $user->id,
            'token' => 'refreshed-fcm-token',
            'installation_id' => 'installation-1',
        ]);

        $this->deleteJson('/api/device-tokens', [
            'token' => 'refreshed-fcm-token',
        ])->assertOk();

        $this->assertDatabaseCount('device_tokens', 0);
    }

    public function test_device_routes_require_authentication(): void
    {
        $this->postJson('/api/device-tokens', [
            'token' => 'token',
            'platform' => 'android',
        ])->assertUnauthorized();

        $this->deleteJson('/api/device-tokens', [
            'token' => 'token',
        ])->assertUnauthorized();
    }

    public function test_device_token_validation_uses_the_unified_api_response(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'phone' => '0590000000',
            'email' => 'test@example.com',
            'address' => 'Test Address',
            'business_activity_type' => 'retail',
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/device-tokens', [
            'platform' => 'windows',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR')
            ->assertJsonStructure([
                'message',
                'error' => [
                    'code',
                    'token',
                    'platform',
                ],
            ]);

        $this->deleteJson('/api/device-tokens')
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR')
            ->assertJsonStructure([
                'message',
                'error' => [
                    'code',
                    'token',
                ],
            ]);
    }

    public function test_device_token_cannot_exceed_the_database_column_length(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'phone' => '0590000000',
            'email' => 'test@example.com',
            'address' => 'Test Address',
            'business_activity_type' => 'retail',
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/device-tokens', [
            'token' => str_repeat('x', 513),
            'platform' => 'android',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'VALIDATION_ERROR')
            ->assertJsonStructure([
                'error' => [
                    'token',
                ],
            ]);
    }
}
