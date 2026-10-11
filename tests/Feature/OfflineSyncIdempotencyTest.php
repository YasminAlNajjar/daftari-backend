<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OfflineSyncIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Offline Test User',
            'phone' => '0590000000',
            'email' => 'offline@example.com',
            'address' => 'Test Address',
            'business_activity_type' => 'retail',
        ]);

        Sanctum::actingAs($this->user);
    }

    public function test_customer_retry_returns_the_existing_customer(): void
    {
        $clientUuid = (string) Str::uuid();
        $payload = $this->customerPayload($clientUuid);

        $firstResponse = $this->postJson('/api/customers', $payload)
            ->assertCreated()
            ->assertJsonPath('data.customer.client_uuid', $clientUuid);

        $customerId = $firstResponse->json('data.customer.id');

        $this->postJson('/api/customers', $payload)
            ->assertOk()
            ->assertJsonPath('data.customer.id', $customerId)
            ->assertJsonPath('data.customer.client_uuid', $clientUuid);

        $this->assertDatabaseCount('customers', 1);
    }

    public function test_customer_uuid_cannot_be_reused_with_different_data(): void
    {
        $clientUuid = (string) Str::uuid();

        $this->postJson(
            '/api/customers',
            $this->customerPayload($clientUuid)
        )->assertCreated();

        $changedPayload = $this->customerPayload($clientUuid);
        $changedPayload['name'] = 'Different Customer';

        $this->postJson('/api/customers', $changedPayload)
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'CLIENT_UUID_CONFLICT');

        $this->assertDatabaseCount('customers', 1);
    }

    public function test_duplicate_phone_with_a_different_uuid_is_rejected(): void
    {
        $this->postJson(
            '/api/customers',
            $this->customerPayload((string) Str::uuid())
        )->assertCreated();

        $this->postJson(
            '/api/customers',
            $this->customerPayload((string) Str::uuid())
        )->assertStatus(422);

        $this->assertDatabaseCount('customers', 1);
    }

    public function test_deleted_customer_uuid_cannot_be_reused(): void
    {
        $clientUuid = (string) Str::uuid();
        $payload = $this->customerPayload($clientUuid);

        $this->postJson('/api/customers', $payload)
            ->assertCreated();

        Customer::query()->sole()->delete();

        $this->postJson('/api/customers', $payload)
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'CLIENT_UUID_CONFLICT');
    }

    public function test_transaction_retry_does_not_duplicate_debt_or_fail_credit_limit(): void
    {
        $customer = $this->createCustomer(100);
        $clientUuid = (string) Str::uuid();
        $payload = $this->transactionPayload($clientUuid, 100);
        $url = "/api/customers/{$customer->id}/transactions";

        $firstResponse = $this->postJson($url, $payload)
            ->assertCreated()
            ->assertJsonPath('data.transaction.client_uuid', $clientUuid)
            ->assertJsonPath('data.balance', '-100.00');

        $transactionId = $firstResponse->json('data.transaction.id');

        $this->postJson($url, $payload)
            ->assertOk()
            ->assertJsonPath('data.transaction.id', $transactionId)
            ->assertJsonPath('data.balance', '-100.00');

        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_offline_customer_can_sync_before_its_transaction(): void
    {
        $customerUuid = (string) Str::uuid();
        $transactionUuid = (string) Str::uuid();
        $customerPayload = $this->customerPayload($customerUuid);

        $customerResponse = $this->postJson(
            '/api/customers',
            $customerPayload
        )->assertCreated();

        $customerId = $customerResponse->json('data.customer.id');
        $transactionPayload = $this->transactionPayload(
            $transactionUuid,
            75
        );
        $transactionUrl =
            "/api/customers/{$customerId}/transactions";

        $transactionResponse = $this->postJson(
            $transactionUrl,
            $transactionPayload
        )->assertCreated();

        $transactionId = $transactionResponse->json(
            'data.transaction.id'
        );

        $this->postJson('/api/customers', $customerPayload)
            ->assertOk()
            ->assertJsonPath('data.customer.id', $customerId);

        $this->postJson($transactionUrl, $transactionPayload)
            ->assertOk()
            ->assertJsonPath('data.transaction.id', $transactionId);

        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_transaction_uuid_cannot_be_reused_with_different_data(): void
    {
        $customer = $this->createCustomer(1000);
        $clientUuid = (string) Str::uuid();
        $url = "/api/customers/{$customer->id}/transactions";

        $this->postJson(
            $url,
            $this->transactionPayload($clientUuid, 100)
        )->assertCreated();

        $this->postJson(
            $url,
            $this->transactionPayload($clientUuid, 200)
        )
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'CLIENT_UUID_CONFLICT');

        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_transaction_uuid_cannot_be_reused_for_another_customer(): void
    {
        $firstCustomer = $this->createCustomer();
        $secondCustomer = $this->createCustomer(
            null,
            '+970599123457'
        );
        $clientUuid = (string) Str::uuid();
        $payload = $this->transactionPayload($clientUuid, 100);

        $this->postJson(
            "/api/customers/{$firstCustomer->id}/transactions",
            $payload
        )->assertCreated();

        $this->postJson(
            "/api/customers/{$secondCustomer->id}/transactions",
            $payload
        )
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'CLIENT_UUID_CONFLICT');

        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_deleted_transaction_uuid_cannot_be_reused(): void
    {
        $customer = $this->createCustomer();
        $clientUuid = (string) Str::uuid();
        $payload = $this->transactionPayload($clientUuid, 100);
        $url = "/api/customers/{$customer->id}/transactions";

        $this->postJson($url, $payload)->assertCreated();

        Transaction::query()->sole()->delete();

        $this->postJson($url, $payload)
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'CLIENT_UUID_CONFLICT');
    }

    public function test_invalid_client_uuid_is_rejected(): void
    {
        $this->postJson(
            '/api/customers',
            $this->customerPayload('not-a-uuid')
        )->assertStatus(422);

        $customer = $this->createCustomer();

        $this->postJson(
            "/api/customers/{$customer->id}/transactions",
            $this->transactionPayload('not-a-uuid', 100)
        )->assertStatus(422);
    }

    private function customerPayload(string $clientUuid): array
    {
        return [
            'client_uuid' => $clientUuid,
            'name' => 'Offline Customer',
            'phone' => '+970599123456',
            'credit_limit' => 1000,
            'notes' => 'Created offline',
        ];
    }

    private function createCustomer(
        ?float $creditLimit = null,
        string $phone = '+970599123456'
    ): Customer {
        return $this->user->customers()->create([
            'name' => 'Existing Customer',
            'phone' => $phone,
            'credit_limit' => $creditLimit,
        ]);
    }

    private function transactionPayload(
        string $clientUuid,
        float $amount
    ): array {
        return [
            'client_uuid' => $clientUuid,
            'type' => Transaction::TYPE_DEBT,
            'amount' => $amount,
            'description' => 'Created offline',
            'transaction_date' => now()
                ->startOfSecond()
                ->toDateTimeString(),
        ];
    }
}
