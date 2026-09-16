<?php

namespace App\Services\Reports;

use Carbon\CarbonImmutable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use App\Exceptions\InvalidPaginationPageException;
use Illuminate\Database\Query\Builder;

abstract class BaseReportService
{
    /*
    |--------------------------------------------------------------------------
    | Day Range
    |--------------------------------------------------------------------------
    */

    protected function getDayRange(
        string $date
    ): array {

        $startDate =
            CarbonImmutable::createFromFormat(
                'Y-m-d',
                $date
            )->startOfDay();

        $endDate =
            $startDate->addDay();

        return [
            $startDate,
            $endDate,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Date Range
    |--------------------------------------------------------------------------
    |
    | toDate محسوب ضمن الفترة.
    |
    | مثال:
    | toDate = 2026-09-08
    |
    | يصبح:
    | < 2026-09-09 00:00:00
    |
    */

    protected function getDateRange(
        string $fromDate,
        string $toDate
    ): array {

        $startDate =
            CarbonImmutable::createFromFormat(
                'Y-m-d',
                $fromDate
            )->startOfDay();

        $endDate =
            CarbonImmutable::createFromFormat(
                'Y-m-d',
                $toDate
            )
                ->startOfDay()
                ->addDay();

        return [
            $startDate,
            $endDate,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Money Formatter
    |--------------------------------------------------------------------------
    */

    protected function money(
        float|string|null $amount
    ): string {

        return number_format(
            (float) ($amount ?? 0),
            2,
            '.',
            ''
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Transaction Formatter
    |--------------------------------------------------------------------------
    */

    protected function formatTransaction(
        object $transaction,
        bool $includeCustomer = false
    ): array {

        $item = [
            'transaction_date' =>
                $transaction->transaction_date,
        ];

        if ($includeCustomer) {
            $item['customer_name'] =
                $transaction->customer_name;
        }

        $item['type'] =
            $transaction->type;

        $item['amount'] =
            $this->money(
                $transaction->amount
            );

        $item['description'] =
            $transaction->description;

        return $item;
    }

    /*
    |--------------------------------------------------------------------------
    | Transactions Collection Formatter
    |--------------------------------------------------------------------------
    |
    | تستخدم في export() لأننا نستخدم get().
    |
    */

    protected function formatTransactionsCollection(
        Collection $transactions,
        bool $includeCustomer = false
    ): array {

        return $transactions
            ->map(
                fn ($transaction) =>
                    $this->formatTransaction(
                        $transaction,
                        $includeCustomer
                    )
            )
            ->values()
            ->all();
    }

    /*
    |--------------------------------------------------------------------------
    | Paginated Transactions Formatter
    |--------------------------------------------------------------------------
    |
    | تستخدم في get() للـ API.
    |
    */

    protected function formatPaginatedTransactions(
        LengthAwarePaginator $paginator,
        bool $includeCustomer = false
    ): void {

        $paginator
            ->getCollection()
            ->transform(
                fn ($transaction) =>
                    $this->formatTransaction(
                        $transaction,
                        $includeCustomer
                    )
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Pagination Formatter
    |--------------------------------------------------------------------------
    */

    protected function pagination(
        LengthAwarePaginator $paginator
    ): array {

        return [
            'current_page' =>
                $paginator->currentPage(),

            'last_page' =>
                $paginator->lastPage(),

            'per_page' =>
                $paginator->perPage(),

            'total' =>
                $paginator->total(),

            'has_next_page' =>
                $paginator->hasMorePages(),

            'has_previous_page' =>
                $paginator->currentPage() > 1,
        ];
    }

    protected function paginate(Builder $query,int $page,int $perPage): LengthAwarePaginator 
    {

        $paginator = $query->paginate(
            perPage: $perPage,
            page: $page
        );

        if (
            $page > $paginator->lastPage()
        ) {
            throw new InvalidPaginationPageException(
                requestedPage: $page,
                lastPage: $paginator->lastPage()
            );
        }

        return $paginator;
    }
}