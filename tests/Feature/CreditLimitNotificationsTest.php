<?php

namespace Tests\Feature;

use App\Enums\UserNotificationType;
use App\Models\Customer;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CreditLimitNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_notifies_when_debt_enters_the_warning_range(): void
    {
        [$user, $customer] = $this->userAndCustomer();

        $this->postDebt($user, $customer, 800)
            ->assertCreated();

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $user->id,
            'type' => UserNotificationType::CREDIT_LIMIT_NEAR->value,
        ]);

        $notification = $user->userNotifications()->sole();

        $this->assertArrayNotHasKey('screen', $notification->data);
    }

    public function test_it_notifies_when_debt_reaches_the_limit(): void
    {
        [$user, $customer] = $this->userAndCustomer();
        $this->existingDebt($user, $customer, 700);

        $this->postDebt($user, $customer, 300)
            ->assertCreated();

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $user->id,
            'type' => UserNotificationType::CREDIT_LIMIT_REACHED->value,
        ]);
    }

    public function test_it_notifies_and_rejects_an_attempt_to_exceed_the_limit(): void
    {
        [$user, $customer] = $this->userAndCustomer();

        $this->postDebt($user, $customer, 1000.01)
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'CREDIT_LIMIT_EXCEEDED');

        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $user->id,
            'type' => UserNotificationType::CREDIT_LIMIT_EXCEEDED_ATTEMPT->value,
        ]);
    }

    public function test_it_does_not_notify_below_the_warning_percentage(): void
    {
        [$user, $customer] = $this->userAndCustomer();

        $this->postDebt($user, $customer, 799.99)
            ->assertCreated();

        $this->assertDatabaseCount('user_notifications', 0);
    }

    private function userAndCustomer(): array
    {
        $user = User::create([
            'name' => 'Test User',
            'phone' => '0590000000',
            'email' => 'test@example.com',
            'address' => 'Test Address',
            'business_activity_type' => 'retail',
        ]);

        $customer = $user->customers()->create([
            'name' => 'Credit Customer',
            'phone' => '0560000000',
            'credit_limit' => 1000,
        ]);

        return [$user, $customer];
    }

    private function existingDebt(
        User $user,
        Customer $customer,
        float $amount
    ): void {
        $customer->transactions()->create([
            'user_id' => $user->id,
            'type' => Transaction::TYPE_DEBT,
            'amount' => $amount,
            'transaction_date' => now(),
        ]);
    }

    private function postDebt(
        User $user,
        Customer $customer,
        float $amount
    ) {
        Sanctum::actingAs($user);

        return $this->postJson(
            "/api/customers/{$customer->id}/transactions",
            [
                'type' => Transaction::TYPE_DEBT,
                'amount' => $amount,
                'transaction_date' => now()->toISOString(),
            ]
        );
    }
}
