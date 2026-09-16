<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Transaction;
use App\Services\Reports\CustomerBalancesReportService;
use App\Services\Reports\CustomerStatementReportService;
use App\Services\Reports\DailyInventoryReportService;
use App\Services\Reports\FinancialSummaryReportService;
use App\Services\Reports\GeneralTransactionsReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Financial Summary
    |--------------------------------------------------------------------------
    */

    public function financialSummary(
        Request $request,
        FinancialSummaryReportService $reportService
    ): JsonResponse {

        $validated = $request->validate([
            'date' => [
                'required',
                'date_format:Y-m-d',
            ],
        ]);

        $report = $reportService->get(
            userId: $request->user()->id,
            date: $validated['date']
        );

        return ApiResponse::success(
            'تم جلب الملخص المالي بنجاح.',
            $report
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Daily Inventory
    |--------------------------------------------------------------------------
    */

    public function dailyInventory(
        Request $request,
        DailyInventoryReportService $reportService
    ): JsonResponse {

        $validated = $request->validate([
            'date' => [
                'required',
                'date_format:Y-m-d',
            ],

            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ]);

        $report = $reportService->get(
            userId: $request->user()->id,
            date: $validated['date'],
            page: (int) ($validated['page'] ?? 1),
            perPage: (int) ($validated['per_page'] ?? 10)
        );

        return ApiResponse::success(
            'تم جلب الجرد اليومي بنجاح.',
            $report
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Customer Statement
    |--------------------------------------------------------------------------
    */

    public function customerStatement(
        Request $request,
        CustomerStatementReportService $reportService
    ): JsonResponse {

        $validated = $request->validate([
            'customer_id' => [
                'required',
                'integer',
            ],

            'from_date' => [
                'required',
                'date_format:Y-m-d',
            ],

            'to_date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:from_date',
            ],

            'type' => [
                'nullable',
                Rule::in([
                    'all',
                    Transaction::TYPE_DEBT,
                    Transaction::TYPE_PAYMENT,
                ]),
            ],

            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ]);

        $userId =
            $request->user()->id;

        /*
        |--------------------------------------------------------------------------
        | Customer Ownership
        |--------------------------------------------------------------------------
        |
        | مهم جدًا:
        | لا نسمح للتاجر بقراءة كشف حساب زبون تابع لتاجر آخر.
        |
        */

        $customer = Customer::query()
            ->where(
                'user_id',
                $userId
            )
            ->where(
                'id',
                $validated['customer_id']
            )
            ->first();

        if (!$customer) {
            return ApiResponse::error(
                'الزبون غير موجود.',
                'CUSTOMER_NOT_FOUND',
                404
            );
        }

        $report = $reportService->get(
            userId: $userId,
            customer: $customer,
            fromDate: $validated['from_date'],
            toDate: $validated['to_date'],
            type: $validated['type'] ?? 'all',
            page: (int) ($validated['page'] ?? 1),
            perPage: (int) ($validated['per_page'] ?? 10)
        );

        return ApiResponse::success(
            'تم جلب كشف حساب الزبون بنجاح.',
            $report
        );
    }

    /*
    |--------------------------------------------------------------------------
    | General Transactions
    |--------------------------------------------------------------------------
    */

    public function generalTransactions(
        Request $request,
        GeneralTransactionsReportService $reportService
    ): JsonResponse {

        $validated = $request->validate([
            'from_date' => [
                'required',
                'date_format:Y-m-d',
            ],

            'to_date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:from_date',
            ],

            'type' => [
                'nullable',
                Rule::in([
                    'all',
                    Transaction::TYPE_DEBT,
                    Transaction::TYPE_PAYMENT,
                ]),
            ],

            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ]);

        $report = $reportService->get(
            userId: $request->user()->id,
            fromDate: $validated['from_date'],
            toDate: $validated['to_date'],
            type: $validated['type'] ?? 'all',
            page: (int) ($validated['page'] ?? 1),
            perPage: (int) ($validated['per_page'] ?? 10)
        );

        return ApiResponse::success(
            'تم جلب سجل المعاملات بنجاح.',
            $report
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Customer Balances
    |--------------------------------------------------------------------------
    */

    public function customerBalances(
        Request $request,
        CustomerBalancesReportService $reportService
    ): JsonResponse {

        $validated = $request->validate([
            'filter' => [
                'nullable',
                Rule::in([
                    'all',
                    'debtors',
                    'credit',
                    'settled',
                ]),
            ],

            'sort' => [
                'nullable',
                Rule::in([
                    'most_indebted',
                    'least_indebted',
                    'name',
                ]),
            ],

            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ]);

        $report = $reportService->get(
            userId: $request->user()->id,
            filter: $validated['filter'] ?? 'all',
            sort: $validated['sort'] ?? 'most_indebted',
            page: (int) ($validated['page'] ?? 1),
            perPage: (int) ($validated['per_page'] ?? 10)
        );

        return ApiResponse::success(
            'تم جلب أرصدة الزبائن بنجاح.',
            $report
        );
    }
}