<?php

namespace App\Services\Reports;

use App\Models\Customer;
use App\Models\ReportExport;
use RuntimeException;

class ReportExportManager
{
    public function __construct(
        private DailyInventoryReportService
            $dailyInventoryReportService,

        private CustomerStatementReportService
            $customerStatementReportService,

        private GeneralTransactionsReportService
            $generalTransactionsReportService,

        private CustomerBalancesReportService
            $customerBalancesReportService
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Prepare Export
    |--------------------------------------------------------------------------
    |
    | هذا هو المدخل الوحيد الذي سيستخدمه GenerateReportPdf.
    |
    | يرجع:
    |
    | report => بيانات التقرير
    | view   => Blade المستخدم لإنشاء PDF
    |
    */

    public function prepare(
        ReportExport $export
    ): array {

        $parameters =
            $export->parameters ?? [];

        return match ($export->report_type) {

            ReportExport::TYPE_DAILY_INVENTORY =>
                $this->dailyInventory(
                    userId: $export->user_id,
                    parameters: $parameters
                ),

            ReportExport::TYPE_CUSTOMER_STATEMENT =>
                $this->customerStatement(
                    userId: $export->user_id,
                    parameters: $parameters
                ),

            ReportExport::TYPE_GENERAL_TRANSACTIONS =>
                $this->generalTransactions(
                    userId: $export->user_id,
                    parameters: $parameters
                ),

            ReportExport::TYPE_CUSTOMER_BALANCES =>
                $this->customerBalances(
                    userId: $export->user_id,
                    parameters: $parameters
                ),

            default =>
                throw new RuntimeException(
                    'UNSUPPORTED_REPORT_TYPE'
                ),
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Daily Inventory
    |--------------------------------------------------------------------------
    */

    private function dailyInventory(
        int $userId,
        array $parameters
    ): array {

        $date =
            $this->requiredParameter(
                $parameters,
                'date'
            );

        $report =
            $this->dailyInventoryReportService
                ->export(
                    userId: $userId,
                    date: $date
                );

        return [
            'report' =>
                $report,

            'view' =>
                'reports.daily_inventory',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Customer Statement
    |--------------------------------------------------------------------------
    */

    private function customerStatement(
        int $userId,
        array $parameters
    ): array {

        $customerId =
            (int)
            $this->requiredParameter(
                $parameters,
                'customer_id'
            );

        $fromDate =
            $this->requiredParameter(
                $parameters,
                'from_date'
            );

        $toDate =
            $this->requiredParameter(
                $parameters,
                'to_date'
            );

        $type =
            $parameters['type']
            ?? 'all';

        /*
        |--------------------------------------------------------------------------
        | Customer Ownership
        |--------------------------------------------------------------------------
        |
        | نتحقق مرة ثانية داخل الـJob flow.
        |
        | حتى لو تم التحقق عند إنشاء export،
        | قد يتغير أو يحذف الزبون قبل تنفيذ الـQueue.
        |
        */

        $customer =
            Customer::query()
                ->where(
                    'id',
                    $customerId
                )
                ->where(
                    'user_id',
                    $userId
                )
                ->first();

        if (!$customer) {
            throw new RuntimeException(
                'CUSTOMER_NOT_FOUND'
            );
        }

        $report =
            $this->customerStatementReportService
                ->export(
                    userId: $userId,
                    customer: $customer,
                    fromDate: $fromDate,
                    toDate: $toDate,
                    type: $type
                );

        return [
            'report' =>
                $report,

            'view' =>
                'reports.customer_statement',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | General Transactions
    |--------------------------------------------------------------------------
    */

    private function generalTransactions(
        int $userId,
        array $parameters
    ): array {

        $fromDate =
            $this->requiredParameter(
                $parameters,
                'from_date'
            );

        $toDate =
            $this->requiredParameter(
                $parameters,
                'to_date'
            );

        $type =
            $parameters['type']
            ?? 'all';

        $report =
            $this->generalTransactionsReportService
                ->export(
                    userId: $userId,
                    fromDate: $fromDate,
                    toDate: $toDate,
                    type: $type
                );

        return [
            'report' =>
                $report,

            'view' =>
                'reports.general_transactions',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Customer Balances
    |--------------------------------------------------------------------------
    */

    private function customerBalances(
        int $userId,
        array $parameters
    ): array {

        $filter =
            $parameters['filter']
            ?? 'all';

        $sort =
            $parameters['sort']
            ?? 'most_indebted';

        $report =
            $this->customerBalancesReportService
                ->export(
                    userId: $userId,
                    filter: $filter,
                    sort: $sort
                );

        return [
            'report' =>
                $report,

            'view' =>
                'reports.customer_balances',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Required Parameter
    |--------------------------------------------------------------------------
    |
    | بدل الوصول المباشر إلى:
    |
    | $parameters['date']
    |
    | ونحصل على Undefined array key.
    |
    */

    private function requiredParameter(
        array $parameters,
        string $key
    ): mixed {

        if (
            !array_key_exists(
                $key,
                $parameters
            )
            ||
            $parameters[$key] === null
            ||
            $parameters[$key] === ''
        ) {
            throw new RuntimeException(
                'REPORT_PARAMETER_MISSING:'
                . $key
            );
        }

        return $parameters[$key];
    }
}