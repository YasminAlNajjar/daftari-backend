<?php

namespace App\Services\Reports;

class CustomerBalancesReportService extends BaseReportService
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
        string $filter = 'all',
        string $sort = 'most_indebted',
        int $page = 1,
        int $perPage = 10
    ): array {

        $prepared =
            $this->prepare(
                userId: $userId,
                filter: $filter,
                sort: $sort
            );

        $customers =
                $this->paginate(
                    query: $prepared['query'],
                    page: $page,
                    perPage: $perPage
                );

        $customers
            ->getCollection()
            ->transform(
                fn ($customer) =>
                    $this->formatCustomer(
                        $customer
                    )
            );

        return $this->buildResponse(
            prepared: $prepared,
            customers:
                $customers->items(),
            pagination:
                $this->pagination(
                    $customers
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
        string $filter = 'all',
        string $sort = 'most_indebted'
    ): array {

        $prepared =
            $this->prepare(
                userId: $userId,
                filter: $filter,
                sort: $sort
            );

        $customers =
            $prepared['query']
                ->get()
                ->map(
                    fn ($customer) =>
                        $this->formatCustomer(
                            $customer
                        )
                )
                ->values()
                ->all();

        return $this->buildResponse(
            prepared: $prepared,
            customers: $customers
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Shared Report Logic
    |--------------------------------------------------------------------------
    */

    private function prepare(
        int $userId,
        string $filter,
        string $sort
    ): array {

        /*
        |--------------------------------------------------------------------------
        | Summary
        |--------------------------------------------------------------------------
        |
        | summary دائمًا لكل الزبائن.
        | لا نمرر filter هنا.
        |
        */

        $summaryData =
            $this->queryService
                ->currentBalanceTotals(
                    $userId
                );

        /*
        |--------------------------------------------------------------------------
        | Customer List
        |--------------------------------------------------------------------------
        |
        | filter و sort يؤثران على القائمة فقط.
        |
        */

        $query =
            $this->queryService
                ->customerBalancesQuery(
                    userId: $userId,
                    filter: $filter,
                    sort: $sort
                );

        return [
            'summary' => [
                'total_outstanding_debt' =>
                    $this->money(
                        $summaryData
                            ->total_outstanding_debt
                    ),

                'total_customer_credit' =>
                    $this->money(
                        $summaryData
                            ->total_customer_credit
                    ),

                'debtors_count' =>
                    (int)
                    $summaryData
                        ->debtors_count,

                'credit_customers_count' =>
                    (int)
                    $summaryData
                        ->credit_customers_count,

                'settled_customers_count' =>
                    (int)
                    $summaryData
                        ->settled_customers_count,

                'total_customers_count' =>
                    (int)
                    $summaryData
                        ->total_customers_count,
            ],

            'filter' =>
                $filter,

            'sort' =>
                $sort,

            'query' =>
                $query,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Customer Formatter
    |--------------------------------------------------------------------------
    */

    private function formatCustomer(
        object $customer
    ): array {

        return [
            'name' =>
                $customer->name,

            'phone' =>
                $customer->phone,

            'balance' =>
                $this->money(
                    $customer->balance
                ),

            'credit_limit' =>
                $customer->credit_limit !== null
                    ? $this->money(
                        $customer->credit_limit
                    )
                    : null,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Response Builder
    |--------------------------------------------------------------------------
    */

    private function buildResponse(
        array $prepared,
        array $customers,
        ?array $pagination = null
    ): array {

        $response = [
            'summary' =>
                $prepared['summary'],

            'filter' =>
                $prepared['filter'],

            'sort' =>
                $prepared['sort'],

            'customers' =>
                $customers,
        ];

        if ($pagination !== null) {
            $response['pagination'] =
                $pagination;
        }

        return $response;
    }
}