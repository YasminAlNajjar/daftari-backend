<?php

namespace App\Services\Reports;

use App\Models\Customer;

class CustomerStatementReportService extends BaseReportService
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
        Customer $customer,
        string $fromDate,
        string $toDate,
        string $type = 'all',
        int $page = 1,
        int $perPage = 10
    ): array {

        $prepared =
            $this->prepare(
                userId: $userId,
                customer: $customer,
                fromDate: $fromDate,
                toDate: $toDate,
                type: $type
            );

        $transactions =
                $this->paginate(
                    query: $prepared['query'],
                    page: $page,
                    perPage: $perPage
                );

        $this->formatPaginatedTransactions(
            $transactions,
            includeCustomer: false
        );

        return $this->buildResponse(
            prepared: $prepared,
            transactions:
                $transactions->items(),
            pagination:
                $this->pagination(
                    $transactions
                )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Export Report
    |--------------------------------------------------------------------------
    */

    public function export(
        int $userId,
        Customer $customer,
        string $fromDate,
        string $toDate,
        string $type = 'all'
    ): array {

        $prepared =
            $this->prepare(
                userId: $userId,
                customer: $customer,
                fromDate: $fromDate,
                toDate: $toDate,
                type: $type
            );

        $transactions =
            $this->formatTransactionsCollection(
                $prepared['query']->get(),
                includeCustomer: false
            );

        return $this->buildResponse(
            prepared: $prepared,
            transactions: $transactions
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Shared Report Logic
    |--------------------------------------------------------------------------
    */

    private function prepare(
        int $userId,
        Customer $customer,
        string $fromDate,
        string $toDate,
        string $type
    ): array {

        [$startDate, $endDate] =
            $this->getDateRange(
                $fromDate,
                $toDate
            );

        /*
        |--------------------------------------------------------------------------
        | Opening Balance
        |--------------------------------------------------------------------------
        */

        $openingBalance =
            $this->queryService
                ->customerOpeningBalance(
                    userId: $userId,
                    customerId: $customer->id,
                    startDate: $startDate
                );

        /*
        |--------------------------------------------------------------------------
        | Real Period Summary
        |--------------------------------------------------------------------------
        |
        | لا نرسل type هنا.
        |
        | لأن فلتر القائمة يجب ألا يغير:
        |
        | opening balance
        | total debts
        | total payments
        | closing balance
        |
        */

        $summaryData =
            $this->queryService
                ->periodSummary(
                    userId: $userId,
                    startDate: $startDate,
                    endDate: $endDate,
                    customerId: $customer->id
                );

        $totalDebts =
            (float)
            $summaryData->total_debts;

        $totalPayments =
            (float)
            $summaryData->total_payments;

        $netMovement =
            $totalPayments
            - $totalDebts;

        $closingBalance =
            $openingBalance
            + $netMovement;

        /*
        |--------------------------------------------------------------------------
        | Transactions
        |--------------------------------------------------------------------------
        |
        | الفلتر يطبق هنا فقط.
        |
        */

        $query =
            $this->queryService
                ->transactionsQuery(
                    userId: $userId,
                    startDate: $startDate,
                    endDate: $endDate,
                    customerId: $customer->id,
                    type: $type
                )
                ->select([
                    'transactions.transaction_date',
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
            'customer' => [
                'name' =>
                    $customer->name,

                'phone' =>
                    $customer->phone,
            ],

            'period' => [
                'from_date' =>
                    $fromDate,

                'to_date' =>
                    $toDate,
            ],

            'filter' => [
                'type' =>
                    $type,
            ],

            'summary' => [
                'opening_balance' =>
                    $this->money(
                        $openingBalance
                    ),

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
                        $netMovement
                    ),

                'closing_balance' =>
                    $this->money(
                        $closingBalance
                    ),
            ],

            'query' =>
                $query,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Response Builder
    |--------------------------------------------------------------------------
    */

    private function buildResponse(
        array $prepared,
        array $transactions,
        ?array $pagination = null
    ): array {

        $response = [
            'customer' =>
                $prepared['customer'],

            'period' =>
                $prepared['period'],

            'filter' =>
                $prepared['filter'],

            'summary' =>
                $prepared['summary'],

            'transactions' =>
                $transactions,
        ];

        if ($pagination !== null) {
            $response['pagination'] =
                $pagination;
        }

        return $response;
    }
}