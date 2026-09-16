<?php

namespace App\Services\Reports;

class DailyInventoryReportService extends BaseReportService
{
    public function __construct(
        private ReportQueryService $queryService
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | API Report
    |--------------------------------------------------------------------------
    */

    public function get(
        int $userId,
        string $date,
        int $page = 1,
        int $perPage = 10
    ): array {

        $prepared =
            $this->prepare(
                userId: $userId,
                date: $date
            );

        $transactions =
                $this->paginate(
                    query: $prepared['query'],
                    page: $page,
                    perPage: $perPage
            );

        $this->formatPaginatedTransactions(
            $transactions,
            includeCustomer: true
        );

        return [
            'date' =>
                $date,

            'summary' =>
                $prepared['summary'],

            'transactions' =>
                $transactions->items(),

            'pagination' =>
                $this->pagination(
                    $transactions
                ),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Export Report
    |--------------------------------------------------------------------------
    */

    public function export(
        int $userId,
        string $date
    ): array {

        $prepared =
            $this->prepare(
                userId: $userId,
                date: $date
            );

        $transactions =
            $this->formatTransactionsCollection(
                $prepared['query']->get(),
                includeCustomer: true
            );

        return [
            'date' =>
                $date,

            'summary' =>
                $prepared['summary'],

            'transactions' =>
                $transactions,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Shared Report Logic
    |--------------------------------------------------------------------------
    */

    private function prepare(
        int $userId,
        string $date
    ): array {

        [$startDate, $endDate] =
            $this->getDayRange(
                $date
            );

        /*
        |--------------------------------------------------------------------------
        | Summary
        |--------------------------------------------------------------------------
        */

        $summaryData =
            $this->queryService
                ->periodSummary(
                    userId: $userId,
                    startDate: $startDate,
                    endDate: $endDate
                );

        $totalDebts =
            (float)
            $summaryData->total_debts;

        $totalPayments =
            (float)
            $summaryData->total_payments;

        $summary = [
            'total_debts' =>
                $this->money(
                    $totalDebts
                ),

            'total_payments' =>
                $this->money(
                    $totalPayments
                ),

            'net_movement' =>
                $this->money(
                    $totalPayments
                    - $totalDebts
                ),

            'debt_transactions_count' =>
                (int)
                $summaryData
                    ->debt_transactions_count,

            'payment_transactions_count' =>
                (int)
                $summaryData
                    ->payment_transactions_count,

            'transactions_count' =>
                (int)
                $summaryData
                    ->transactions_count,

            'active_customers_count' =>
                (int)
                $summaryData
                    ->active_customers_count,
        ];

        /*
        |--------------------------------------------------------------------------
        | Transactions
        |--------------------------------------------------------------------------
        */

        $query =
            $this->queryService
                ->transactionsQuery(
                    userId: $userId,
                    startDate: $startDate,
                    endDate: $endDate
                )
                ->select([
                    'transactions.transaction_date',
                    'customers.name as customer_name',
                    'transactions.type',
                    'transactions.amount',
                    'transactions.description',
                ])
                ->orderByDesc(
                    'transactions.transaction_date'
                )
                ->orderByDesc(
                    'transactions.id'
                );

        return [
            'summary' =>
                $summary,

            'query' =>
                $query,
        ];
    }
}