<?php

namespace App\Services\Reports;

use App\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ReportQueryService
{
    /*
    |--------------------------------------------------------------------------
    | Transactions Query
    |--------------------------------------------------------------------------
    |
    | الاستعلام الأساسي لجميع التقارير التي تعتمد على المعاملات.
    |
    */

    public function transactionsQuery(
        int $userId,
        ?CarbonImmutable $startDate = null,
        ?CarbonImmutable $endDate = null,
        ?int $customerId = null,
        string $type = 'all'
    ): Builder {

        $query = DB::table('transactions')
            ->join(
                'customers',
                'customers.id',
                '=',
                'transactions.customer_id'
            )
            ->where(
                'transactions.user_id',
                $userId
            )
            ->whereNull(
                'transactions.deleted_at'
            )
            ->whereNull(
                'customers.deleted_at'
            );

        if ($startDate !== null) {
            $query->where(
                'transactions.transaction_date',
                '>=',
                $startDate
            );
        }

        if ($endDate !== null) {
            $query->where(
                'transactions.transaction_date',
                '<',
                $endDate
            );
        }

        if ($customerId !== null) {
            $query->where(
                'transactions.customer_id',
                $customerId
            );
        }

        $this->applyTransactionTypeFilter(
            $query,
            $type
        );

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | Period Summary
    |--------------------------------------------------------------------------
    |
    | إجماليات المعاملات داخل فترة محددة.
    |
    */

    public function periodSummary(
        int $userId,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        ?int $customerId = null,
        string $type = 'all'
    ): object {

        return $this
            ->transactionsQuery(
                userId: $userId,
                startDate: $startDate,
                endDate: $endDate,
                customerId: $customerId,
                type: $type
            )
            ->selectRaw(
                '
                COALESCE(
                    SUM(
                        CASE
                            WHEN transactions.type = ?
                                THEN transactions.amount
                            ELSE 0
                        END
                    ),
                    0
                ) AS total_debts,

                COALESCE(
                    SUM(
                        CASE
                            WHEN transactions.type = ?
                                THEN transactions.amount
                            ELSE 0
                        END
                    ),
                    0
                ) AS total_payments,

                COALESCE(
                    SUM(
                        CASE
                            WHEN transactions.type = ?
                                THEN 1
                            ELSE 0
                        END
                    ),
                    0
                ) AS debt_transactions_count,

                COALESCE(
                    SUM(
                        CASE
                            WHEN transactions.type = ?
                                THEN 1
                            ELSE 0
                        END
                    ),
                    0
                ) AS payment_transactions_count,

                COUNT(transactions.id)
                    AS transactions_count,

                COUNT(
                    DISTINCT transactions.customer_id
                ) AS active_customers_count
                ',
                [
                    Transaction::TYPE_DEBT,
                    Transaction::TYPE_PAYMENT,
                    Transaction::TYPE_DEBT,
                    Transaction::TYPE_PAYMENT,
                ]
            )
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Customer Opening Balance
    |--------------------------------------------------------------------------
    |
    | رصيد الزبون قبل بداية الفترة المطلوبة.
    |
    */

    public function customerOpeningBalance(
        int $userId,
        int $customerId,
        CarbonImmutable $startDate
    ): float {

        $result = $this
            ->transactionsQuery(
                userId: $userId,
                endDate: $startDate,
                customerId: $customerId
            )
            ->selectRaw(
                '
                COALESCE(
                    SUM(
                        CASE
                            WHEN transactions.type = ?
                                THEN transactions.amount

                            WHEN transactions.type = ?
                                THEN -transactions.amount

                            ELSE 0
                        END
                    ),
                    0
                ) AS opening_balance
                ',
                [
                    Transaction::TYPE_PAYMENT,
                    Transaction::TYPE_DEBT,
                ]
            )
            ->first();

        return (float) $result->opening_balance;
    }

    /*
    |--------------------------------------------------------------------------
    | Customer Balances Query
    |--------------------------------------------------------------------------
    |
    | قائمة الزبائن مع الرصيد الحالي.
    | يبدأ من customers حتى يظهر الزبون بدون معاملات أيضًا.
    |
    */

    public function customerBalancesQuery(
        int $userId,
        string $filter = 'all',
        string $sort = 'most_indebted'
    ): Builder {

        $balancesSubQuery =
            $this->baseCustomerBalancesQuery(
                $userId
            );

        $query = DB::query()
            ->fromSub(
                $balancesSubQuery,
                'customer_balances'
            );

        $this->applyBalanceFilter(
            $query,
            $filter
        );

        $this->applyBalanceSort(
            $query,
            $sort
        );

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | Current Balance Totals
    |--------------------------------------------------------------------------
    |
    | الوضع المالي الحالي لجميع الزبائن.
    |
    */

    public function currentBalanceTotals(
        int $userId
    ): object {

        $customerBalances =
            $this->baseCustomerBalancesQuery(
                $userId
            );

        return DB::query()
            ->fromSub(
                $customerBalances,
                'customer_balances'
            )
            ->selectRaw(
                '
                COALESCE(
                    SUM(
                        CASE
                            WHEN balance < 0
                                THEN ABS(balance)
                            ELSE 0
                        END
                    ),
                    0
                ) AS total_outstanding_debt,

                COALESCE(
                    SUM(
                        CASE
                            WHEN balance > 0
                                THEN balance
                            ELSE 0
                        END
                    ),
                    0
                ) AS total_customer_credit,

                COALESCE(
                    SUM(
                        CASE
                            WHEN balance < 0
                                THEN 1
                            ELSE 0
                        END
                    ),
                    0
                ) AS debtors_count,

                COALESCE(
                    SUM(
                        CASE
                            WHEN balance > 0
                                THEN 1
                            ELSE 0
                        END
                    ),
                    0
                ) AS credit_customers_count,

                COALESCE(
                    SUM(
                        CASE
                            WHEN balance = 0
                                THEN 1
                            ELSE 0
                        END
                    ),
                    0
                ) AS settled_customers_count,

                COUNT(*)
                    AS total_customers_count
                '
            )
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Base Customer Balances Query
    |--------------------------------------------------------------------------
    */

    private function baseCustomerBalancesQuery(
        int $userId
    ): Builder {

        return DB::table('customers')
            ->leftJoin(
                'transactions',
                function ($join) use ($userId) {

                    $join->on(
                        'transactions.customer_id',
                        '=',
                        'customers.id'
                    )
                        ->where(
                            'transactions.user_id',
                            '=',
                            $userId
                        )
                        ->whereNull(
                            'transactions.deleted_at'
                        );
                }
            )
            ->where(
                'customers.user_id',
                $userId
            )
            ->whereNull(
                'customers.deleted_at'
            )
            ->selectRaw(
                '
                customers.id AS customer_id,
                customers.name,
                customers.phone,
                customers.credit_limit,

                COALESCE(
                    SUM(
                        CASE
                            WHEN transactions.type = ?
                                THEN transactions.amount

                            WHEN transactions.type = ?
                                THEN -transactions.amount

                            ELSE 0
                        END
                    ),
                    0
                ) AS balance
                ',
                [
                    Transaction::TYPE_PAYMENT,
                    Transaction::TYPE_DEBT,
                ]
            )
            ->groupBy(
                'customers.id',
                'customers.name',
                'customers.phone',
                'customers.credit_limit'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Transaction Type Filter
    |--------------------------------------------------------------------------
    */

    private function applyTransactionTypeFilter(
        Builder $query,
        string $type
    ): void {

        if ($type === Transaction::TYPE_DEBT) {
            $query->where(
                'transactions.type',
                Transaction::TYPE_DEBT
            );
        }

        if ($type === Transaction::TYPE_PAYMENT) {
            $query->where(
                'transactions.type',
                Transaction::TYPE_PAYMENT
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Balance Filter
    |--------------------------------------------------------------------------
    */

    private function applyBalanceFilter(
        Builder $query,
        string $filter
    ): void {

        match ($filter) {

            'debtors' =>
                $query->where(
                    'balance',
                    '<',
                    0
                ),

            'credit' =>
                $query->where(
                    'balance',
                    '>',
                    0
                ),

            'settled' =>
                $query->where(
                    'balance',
                    '=',
                    0
                ),

            default => null,
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Balance Sort
    |--------------------------------------------------------------------------
    */

    private function applyBalanceSort(
        Builder $query,
        string $sort
    ): void {

        match ($sort) {

            'most_indebted' =>
                $query->orderBy(
                    'balance',
                    'asc'
                ),

            'least_indebted' =>
                $query->orderBy(
                    'balance',
                    'desc'
                ),

            'name' =>
                $query->orderBy(
                    'name',
                    'asc'
                ),

            default =>
                $query->orderBy(
                    'balance',
                    'asc'
                ),
        };
    }
}