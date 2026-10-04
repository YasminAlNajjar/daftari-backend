<?php

namespace Tests\Feature;

use App\Enums\UserNotificationType;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyDataReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_only_reminds_users_without_entries_today_and_does_not_duplicate(): void
    {
        $inactiveUser = $this->createUser('inactive@example.com', '0590000001');
        $activeUser = $this->createUser('active@example.com', '0590000002');

        $customer = $activeUser->customers()->create([
            'name' => 'Active Customer',
            'phone' => '0560000001',
            'credit_limit' => 1000,
        ]);

        $customer->transactions()->create([
            'user_id' => $activeUser->id,
            'type' => Transaction::TYPE_DEBT,
            'amount' => 10,
            'transaction_date' => now(),
        ]);

        $this->artisan('notifications:send-daily-reminders')->assertSuccessful();
        $this->artisan('notifications:send-daily-reminders')->assertSuccessful();

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $inactiveUser->id,
            'type' => UserNotificationType::DAILY_DATA_REMINDER->value,
        ]);
        $this->assertDatabaseMissing('user_notifications', [
            'user_id' => $activeUser->id,
            'type' => UserNotificationType::DAILY_DATA_REMINDER->value,
        ]);
        $this->assertDatabaseCount('user_notifications', 1);

        $notification = $inactiveUser->userNotifications()->sole();

        $this->assertArrayNotHasKey('screen', $notification->data);
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
}
