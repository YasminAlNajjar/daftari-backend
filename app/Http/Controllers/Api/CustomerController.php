<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Resources\CustomerResource;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\Customer\ListCustomersRequest;
use App\Http\Resources\CustomerListResource;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $customer = $request->user()
            ->customers()
            ->create($request->validated());

        return ApiResponse::success(
            'تمت إضافة الزبون بنجاح.',
            [
                'customer' => new CustomerResource($customer),
            ],
            201
        );
    }

    public function index(ListCustomersRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $search = $validated['search'] ?? null;
        $perPage = $validated['per_page'] ?? 15;

        $customers = $request->user()
         ->customers()
         ->when($search, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
         })
         ->orderBy('name')
         ->orderBy('id')
         ->paginate($perPage)
         ->withQueryString();

        return ApiResponse::success(
         'تم جلب الزبائن بنجاح.',
          [
            'customers' => CustomerListResource::collection(
                $customers->getCollection()
            ),

            'pagination' => [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
                'from' => $customers->firstItem(),
                'to' => $customers->lastItem(),
                'has_more_pages' => $customers->hasMorePages(),
             ],
            ]
     );
    }

    public function update(UpdateCustomerRequest $request,int $customer): JsonResponse {
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

         if (empty($validated)) {
            return ApiResponse::error(
                'لم يتم إرسال أي بيانات للتعديل.',
                'NO_FIELDS_TO_UPDATE',
                422
            );
        }

        $customerModel->update($validated);

        $customerModel->refresh();

        return ApiResponse::success(
            'تم تعديل بيانات الزبون بنجاح.',
            [
                'customer' => new CustomerResource($customerModel),
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
}