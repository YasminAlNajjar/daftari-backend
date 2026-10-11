<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerListQueryCountTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Query Count User',
            'phone' => '0591111111',
            'email' => 'query-count@example.com',
            'address' => 'Test Address',
            'business_activity_type' => 'retail',
        ]);

        Sanctum::actingAs($this->user);
    }

    public function test_customer_list_query_count_does_not_grow_with_customers(): void
    {
        $this->createCustomerWithTransactions(1);

        $singleCustomerQueries = $this->customerListQueryCount();

        foreach (range(2, 10) as $number) {
            $this->createCustomerWithTransactions($number);
        }

        $tenCustomerQueries = $this->customerListQueryCount();

        $this->assertSame(
            $singleCustomerQueries,
            $tenCustomerQueries,
            'Customer list query count must remain constant.'
        );
        $this->assertLessThanOrEqual(3, $tenCustomerQueries);
    }

    public function test_single_customer_balance_uses_one_query(): void
    {
        $customer = $this->createCustomerWithTransactions(1);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $balance = $customer->balance;
        $queryCount = count(DB::getQueryLog());

        DB::disableQueryLog();

        $this->assertSame('-60.00', $balance);
        $this->assertSame(1, $queryCount);
    }

    private function customerListQueryCount(): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->getJson('/api/customers?per_page=10');
        $queryCount = count(DB::getQueryLog());

        DB::disableQueryLog();

        $response
            ->assertOk()
            ->assertJsonPath('data.customers.0.balance', '-60.00');

        return $queryCount;
    }

    private function createCustomerWithTransactions(int $number): Customer
    {
        $customer = $this->user->customers()->create([
            'name' => "Customer {$number}",
            'phone' => '+970599'.str_pad(
                (string) $number,
                6,
                '0',
                STR_PAD_LEFT
            ),
        ]);

        $customer->transactions()->createMany([
            [
                'user_id' => $this->user->id,
                'type' => Transaction::TYPE_DEBT,
                'amount' => 100,
                'transaction_date' => now(),
            ],
            [
                'user_id' => $this->user->id,
                'type' => Transaction::TYPE_PAYMENT,
                'amount' => 40,
                'transaction_date' => now(),
            ],
        ]);

        return $customer;
    }
}
