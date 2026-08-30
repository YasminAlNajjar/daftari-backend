<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\ListTransactionsRequest;
use App\Http\Requests\Transaction\StoreTransactionRequest;
use App\Http\Requests\Transaction\UpdateTransactionRequest;
use App\Http\Resources\TransactionListResource;
use App\Http\Resources\TransactionResource;
use App\Models\Customer;
use App\Models\Transaction;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{

    public function store(StoreTransactionRequest $request,int $customer): JsonResponse {
        $customerModel = $this->findOwnedCustomerOrFail(
            $request,
            $customer
        );

        $validated = $request->validated();

        /*
         * فحص Credit Limit عند إضافة Debt.
         */
        if (
            $validated['type'] === Transaction::TYPE_DEBT
            && $customerModel->credit_limit !== null
        ) {
            $currentBalance = (float) $customerModel->balance;
            $newDebtAmount = (float) $validated['amount'];

            /*
             * Balance = Payments - Debts
             */
            $newBalance = $currentBalance - $newDebtAmount;

            /*
             * إذا كان الرصيد سالبًا،
             * القيمة المطلقة تمثل الدين الفعلي.
             */
            $resultingDebt = abs(
                min($newBalance, 0)
            );

            if (
                $resultingDebt >
                (float) $customerModel->credit_limit
            ) {
                return ApiResponse::error(
                    'هذه العملية تتجاوز سقف الدين المحدد للزبون.',
                    'CREDIT_LIMIT_EXCEEDED',
                    422
                );
            }
        }

        $transaction = $customerModel
            ->transactions()
            ->create([
                'user_id' => $request->user()->id,
                'type' => $validated['type'],
                'amount' => $validated['amount'],
                'description' =>
                    $validated['description'] ?? null,
                'transaction_date' =>
                    $validated['transaction_date'],
            ]);

        $customerModel->refresh();

        return ApiResponse::success(
            'تمت إضافة المعاملة بنجاح.',
            [
                'transaction' => new TransactionResource(
                    $transaction
                ),
                'balance' => $customerModel->balance,
            ],
            201
        );
    }


    public function index(ListTransactionsRequest $request,int $customer): JsonResponse {
        $customerModel = $this->findOwnedCustomerOrFail(
            $request,
            $customer
        );

        $validated = $request->validated();

        $perPage = $validated['per_page'] ?? 10;

        $transactions = $customerModel
            ->transactions()
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        /*
         * صفحة أكبر من آخر صفحة موجودة.
         */
        if (
            $transactions->currentPage() >
                $transactions->lastPage()
            && $transactions->total() > 0
        ) {
            return ApiResponse::error(
                'رقم الصفحة المطلوبة غير موجود.',
                'PAGE_NOT_FOUND',
                404
            );
        }

        return ApiResponse::success(
            'تم جلب معاملات الزبون بنجاح.',
            [
                'transactions' =>
                    TransactionListResource::collection(
                        $transactions->getCollection()
                    ),

                'balance' => $customerModel->balance,

                'pagination' => [
                    'current_page' =>
                        $transactions->currentPage(),

                    'last_page' =>
                        $transactions->lastPage(),

                    'per_page' =>
                        $transactions->perPage(),

                    'total' =>
                        $transactions->total(),

                    'has_next_page' =>
                        $transactions->hasMorePages(),

                    'has_previous_page' =>
                        $transactions->currentPage() > 1,
                ],
            ]
        );
    }


    public function show(Request $request,int $customer,int $transaction): JsonResponse {
        $customerModel = $this->findOwnedCustomerOrFail(
            $request,
            $customer
        );

        $transactionModel =
            $this->findCustomerTransactionOrFail(
                $customerModel,
                $transaction
            );

        return ApiResponse::success(
            'تم جلب بيانات المعاملة بنجاح.',
            [
                'transaction' => new TransactionResource(
                    $transactionModel
                ),
                'balance' => $customerModel->balance,
            ]
        );
    }


    public function update(UpdateTransactionRequest $request,int $customer,int $transaction): JsonResponse {
        $customerModel = $this->findOwnedCustomerOrFail(
            $request,
            $customer
        );

        $transactionModel =
            $this->findCustomerTransactionOrFail(
                $customerModel,
                $transaction
            );

        $validated = $request->validated();

        if (empty($validated)) {
            return ApiResponse::error(
                'لم يتم إرسال أي بيانات للتعديل.',
                'NO_FIELDS_TO_UPDATE',
                422
            );
        }

        /*
         * القيم الجديدة.
         */
        $newType = $validated['type']
            ?? $transactionModel->type;

        $newAmount = (float) (
            $validated['amount']
            ?? $transactionModel->amount
        );

        /*
         * الرصيد الحالي يحتوي على أثر
         * المعاملة القديمة.
         */
        $currentBalance = (float) $customerModel->balance;

        /*
         * إزالة أثر المعاملة القديمة.
         */
        if ($transactionModel->isDebt()) {
            $balanceWithoutOldTransaction =
                $currentBalance
                + (float) $transactionModel->amount;
        } else {
            $balanceWithoutOldTransaction =
                $currentBalance
                - (float) $transactionModel->amount;
        }

        /*
         * إضافة أثر المعاملة الجديدة.
         */
        if ($newType === Transaction::TYPE_DEBT) {
            $newBalance =
                $balanceWithoutOldTransaction
                - $newAmount;
        } else {
            $newBalance =
                $balanceWithoutOldTransaction
                + $newAmount;
        }

        $currentDebt = abs(
            min($currentBalance, 0)
        );

        $resultingDebt = abs(
            min($newBalance, 0)
        );

        /*
         * نتحقق من Credit Limit إذا
         * كان التعديل سيزيد الدين.
         */
        if (
            $customerModel->credit_limit !== null
            && $resultingDebt > $currentDebt
            && $resultingDebt >
                (float) $customerModel->credit_limit
        ) {
            return ApiResponse::error(
                'هذه العملية تتجاوز سقف الدين المحدد للزبون.',
                'CREDIT_LIMIT_EXCEEDED',
                422
            );
        }

        $transactionModel->update($validated);

        $transactionModel->refresh();
        $customerModel->refresh();

        return ApiResponse::success(
            'تم تعديل المعاملة بنجاح.',
            [
                'transaction' => new TransactionResource(
                    $transactionModel
                ),
                'balance' => $customerModel->balance,
            ]
        );
    }

    public function destroy(Request $request,int $customer,int $transaction): JsonResponse {
        $customerModel = $this->findOwnedCustomerOrFail(
            $request,
            $customer
        );

        $transactionModel =
            $this->findCustomerTransactionOrFail(
                 $customerModel,
                 $transaction
            );

    /*
     * Soft Delete
     */
        $transactionModel->delete();

    /*
     * إعادة تحميل الزبون حتى نحصل
     * على الرصيد بعد حذف المعاملة.
     */
        $customerModel->refresh();

        return ApiResponse::success(
            'تم حذف المعاملة بنجاح.',
            [
                'balance' => $customerModel->balance,
            ]
        );
    }


//***********************  Helper Functions ***************************
    private function findOwnedCustomerOrFail(Request $request,int $customerId ): Customer {
        $customer = $request->user()
            ->customers()
            ->find($customerId);

        if (! $customer) {
            throw new HttpResponseException(
                ApiResponse::error(
                    'الزبون غير موجود.',
                    'CUSTOMER_NOT_FOUND',
                    404
                )
            );
        }

        return $customer;
    }


    private function findCustomerTransactionOrFail(Customer $customer,int $transactionId): Transaction {
        $transaction = $customer
            ->transactions()
            ->find($transactionId);

        if (! $transaction) {
            throw new HttpResponseException(
                ApiResponse::error(
                    'المعاملة غير موجودة.',
                    'TRANSACTION_NOT_FOUND',
                    404
                )
            );
        }

        return $transaction;
    }

    
}