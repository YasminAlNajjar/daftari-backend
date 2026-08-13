<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * تنظيف رقم الهاتف قبل التحقق.
     *
     * 059-123-4567
     * يصبح:
     * 0591234567
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('phone')) {
            $phone = preg_replace(
                '/[^0-9]/',
                '',
                (string) $this->input('phone')
            );

            $this->merge([
                'phone' => $phone,
            ]);
        }
    }

    public function rules(): array
    {
        $customerId = $this->route('customer');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:100',
            ],

            'phone' => [
                'sometimes',
                'required',
                'string',
                'regex:/^[0-9]{10}$/',

                Rule::unique('customers', 'phone')
                    ->where(
                        fn ($query) => $query->where(
                            'user_id',
                            $this->user()->id
                        )
                    )
                    ->ignore($customerId),
            ],

            'credit_limit' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
                'max:9999999999999.99',
            ],

            'notes' => [
                'sometimes',
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم الزبون مطلوب.',
            'name.string' => 'اسم الزبون يجب أن يكون نصًا.',
            'name.max' => 'اسم الزبون يجب ألا يتجاوز 100 حرف.',

            'phone.required' => 'رقم هاتف الزبون مطلوب.',
            'phone.string' => 'رقم هاتف الزبون يجب أن يكون نصًا.',
            'phone.regex' => 'رقم هاتف الزبون يجب أن يتكون من 10 أرقام.',
            'phone.unique' => 'يوجد زبون مسجل بهذا الرقم مسبقًا.',

            'credit_limit.numeric' => 'الحد الائتماني يجب أن يكون رقمًا.',
            'credit_limit.min' => 'الحد الائتماني يجب ألا يكون أقل من صفر.',
            'credit_limit.max' => 'قيمة الحد الائتماني تتجاوز الحد المسموح.',

            'notes.string' => 'الملاحظات يجب أن تكون نصًا.',
            'notes.max' => 'الملاحظات يجب ألا تتجاوز 1000 حرف.',
        ];
    }
}