<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\ListCustomersRequest;
use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Http\Resources\CustomerListResource;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $clientUuid = $validated['client_uuid'] ?? null;

        if ($clientUuid !== null) {
            $existingCustomer = $this->findCustomerByClientUuid(
                $request,
                $clientUuid
            );

            if ($existingCustomer !== null) {
                return $this->existingCustomerResponse(
                    $existingCustomer,
                    $validated
                );
            }
        }

        try {
            $customer = $request->user()
                ->customers()
                ->create($validated);
        } catch (UniqueConstraintViolationException $exception) {
            /*
             * قد يصل طلبان بنفس UUID في اللحظة نفسها. يعيد الطلب
             * الخاسر في سباق الإنشاء السجل الذي أنشأه الطلب الآخر.
             */
            if ($clientUuid === null) {
                throw $exception;
            }

            $existingCustomer = $this->findCustomerByClientUuid(
                $request,
                $clientUuid
            );

            if ($existingCustomer === null) {
                throw $exception;
            }

            return $this->existingCustomerResponse(
                $existingCustomer,
                $validated
            );
        }

        return ApiResponse::success(
            'تمت إضافة الزبون بنجاح.',
            [
                'customer' => new CustomerResource($customer),
            ],
            201
        );
    }

    private function findCustomerByClientUuid(
        StoreCustomerRequest $request,
        string $clientUuid
    ): ?Customer {
        return $request->user()
            ->customers()
            ->withTrashed()
            ->where('client_uuid', $clientUuid)
            ->first();
    }

    private function existingCustomerResponse(
        Customer $customer,
        array $validated
    ): JsonResponse {
        if (
            $customer->trashed()
            || ! $this->customerMatchesRequest($customer, $validated)
        ) {
            return ApiResponse::error(
                'معرّف المزامنة مستخدم مسبقًا مع بيانات مختلفة.',
                'CLIENT_UUID_CONFLICT',
                409
            );
        }

        return ApiResponse::success(
            'الزبون متزامن مسبقًا.',
            [
                'customer' => new CustomerResource($customer),
            ],
            200
        );
    }

    private function customerMatchesRequest(
        Customer $customer,
        array $validated
    ): bool {
        return $customer->name === $validated['name']
            && $customer->phone === $validated['phone']
            && $customer->notes === ($validated['notes'] ?? null)
            && $this->normalizeMoney($customer->credit_limit)
                === $this->normalizeMoney(
                    $validated['credit_limit'] ?? null
                );
    }

    private function normalizeMoney(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return number_format((float) $value, 2, '.', '');
    }

    public function index(ListCustomersRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $search = $validated['search'] ?? null;
        $perPage = $validated['per_page'] ?? 10;

        $customers = $request->user()
            ->customers()
            ->withBalance()

        /*
         * البحث بالاسم أو رقم الهاتف.
         */
            ->when($search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name','like',"%{$search}%" )
                            ->orWhere('phone','like',"%{$search}%");
                });
            })

        /*
         * أحدث زبون يظهر أولًا.
         */
            ->orderByDesc('id')

        /*
         * Pagination.
         */
            ->paginate($perPage)
            ->withQueryString();
        /*
        * التحقق من أن رقم الصفحة المطلوب موجود.
        */

        if ($customers->currentPage() > $customers->lastPage()&& $customers->total() > 0) {
            return ApiResponse::error(
                'رقم الصفحة المطلوبة غير موجود.',
                'PAGE_NOT_FOUND',
                 404
            );
        }

        return ApiResponse::success(
            'تم جلب الزبائن بنجاح.',
            [
                'customers' => CustomerListResource::collection(
                    $customers->getCollection()
                ),

                'pagination' => [
                    'current_page'
                        => $customers->currentPage(),

                    'last_page'
                        => $customers->lastPage(),

                    'per_page'
                        => $customers->perPage(),

                    'total'
                        => $customers->total(),

                    'has_next_page'
                        => $customers->hasMorePages(),

                    'has_previous_page'
                        => $customers->currentPage() > 1,
                ],
            ]
        );

    }

    public function update(UpdateCustomerRequest $request,int $customer): JsonResponse {
    /*
     * البحث فقط ضمن زبائن المستخدم الحالي.
     * Soft-deleted customers يتم استبعادهم .
     */
        $customerModel = $request->user()
            ->customers()
            ->find($customer);

        if (! $customerModel) {
            return ApiResponse::error(
                'الزبون غير موجود.',
                'CUSTOMER_NOT_FOUND',
                404
            );
        }

        $validated = $request->validated();

    /*
     * منع PATCH فارغ.
     */
        if (empty($validated)) {
            return ApiResponse::error(
                'لم يتم إرسال أي بيانات للتعديل.',
                'NO_FIELDS_TO_UPDATE',
                422
            );
        }

    /*
     * تحديث البيانات.
     */
        $customerModel->update($validated);

    /*
     * إعادة تحميل البيانات بعد التعديل.
     */
        $customerModel->refresh();

        return ApiResponse::success(
            'تم تعديل بيانات الزبون بنجاح.',
            [
                'customer' => new CustomerResource(
                    $customerModel
                ),
            ]
        );
    }

    public function show(Request $request,int $customer): JsonResponse {
        $customerModel = $request->user()
            ->customers()
            ->find($customer);

        if (! $customerModel) {
            return ApiResponse::error(
                'الزبون غير موجود.',
                'CUSTOMER_NOT_FOUND',
                404
            );
        }

        return ApiResponse::success(
            'تم جلب بيانات الزبون بنجاح.',
            [
                'customer' => new CustomerResource($customerModel),
            ]
        );
    }

    public function destroy(Request $request,int $customer): JsonResponse {
    /*
     * البحث فقط ضمن زبائن المستخدم الحالي.
     */
        $customerModel = $request->user()
            ->customers()
            ->find($customer);

        if (! $customerModel) {
             return ApiResponse::error(
                'الزبون غير موجود.',
                'CUSTOMER_NOT_FOUND',
                404
            );
        }

        $customerModel->delete();

         return ApiResponse::success(
            'تم حذف الزبون بنجاح.'
        );
    }
}
