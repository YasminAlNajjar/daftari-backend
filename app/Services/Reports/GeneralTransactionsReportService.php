<?php

namespace App\Services\Reports;

class GeneralTransactionsReportService extends BaseReportService
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
        string $fromDate,
        string $toDate,
        string $type = 'all',
        int $page = 1,
        int $perPage = 10
    ): array {

        $prepared =
            $this->prepare(
                userId: $userId,
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
            includeCustomer: true
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
        string $fromDate,
        string $toDate,
        string $type = 'all'
    ): array {

        $prepared =
            $this->prepare(
                userId: $userId,
                fromDate: $fromDate,
                toDate: $toDate,
                type: $type
            );

        $transactions =
            $this->formatTransactionsCollection(
                $prepared['query']->get(),
                includeCustomer: true
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
        | Summary
        |--------------------------------------------------------------------------
        |
        | هنا type يؤثر على الملخص أيضًا.
        |
        */

        $summaryData =
            $this->queryService
                ->periodSummary(
                    userId: $userId,
                    startDate: $startDate,
                    endDate: $endDate,
                    type: $type
                );

        $totalDebts =
            (float)
            $summaryData->total_debts;

        $totalPayments =
            (float)
            $summaryData->total_payments;

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
                    endDate: $endDate,
                    type: $type
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

                'transactions_count' =>
                    (int)
                    $summaryData
                        ->transactions_count,
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