<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use App\Services\Reports\GeneralTransactionsReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ReportExportLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_allows_rows_up_to_the_configured_limit(): void
    {
        $user = $this->userWithTransactions(3);
        config(['reports.max_export_rows' => 3]);

        $report = app(GeneralTransactionsReportService::class)
            ->export(
                userId: $user->id,
                fromDate: now()->toDateString(),
                toDate: now()->toDateString()
            );

        $this->assertCount(3, $report['transactions']);
    }

    public function test_export_rejects_rows_above_the_configured_limit(): void
    {
        $user = $this->userWithTransactions(3);
        config(['reports.max_export_rows' => 2]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'REPORT_EXPORT_ROW_LIMIT_EXCEEDED'
        );

        app(GeneralTransactionsReportService::class)
            ->export(
                userId: $user->id,
                fromDate: now()->toDateString(),
                toDate: now()->toDateString()
            );
    }

    private function userWithTransactions(int $count): User
    {
        $user = User::create([
            'name' => 'Report Limit User',
            'phone' => '0592222222',
            'email' => 'report-limit@example.com',
            'address' => 'Test Address',
            'business_activity_type' => 'retail',
        ]);

        $customer = $user->customers()->create([
            'name' => 'Report Customer',
            'phone' => '+970599222222',
        ]);

        foreach (range(1, $count) as $number) {
            $customer->transactions()->create([
                'user_id' => $user->id,
                'type' => Transaction::TYPE_DEBT,
                'amount' => $number,
                'transaction_date' => now()->subMinutes($number),
            ]);
        }

        return $user;
    }
}
