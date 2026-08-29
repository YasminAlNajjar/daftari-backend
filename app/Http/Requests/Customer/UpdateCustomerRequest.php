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
     * توحيد صيغة رقم الهاتف قبل التحقق.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('phone')) {
            $phone = trim((string) $this->input('phone'));

            if (str_starts_with($phone, '+')) {
                $phone = '+' . (
                    preg_replace(
                        '/\D+/',
                        '',
                        substr($phone, 1)
                    ) ?? ''
                );
            }

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
                'regex:/^\+(?:970|972)(?:59|56)\d{7}$/',

                Rule::unique('customers', 'phone')
                    ->where(function ($query) {
                        return $query
                            ->where(
                                'user_id',
                                $this->user()->id
                            )
                            ->whereNull('deleted_at');
                    })
                    ->ignore($customerId),
            ],

            'credit_limit' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
                'max:999999.99',
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
            'name.required'
                => 'اسم الزبون مطلوب.',

            'name.string'
                => 'اسم الزبون يجب أن يكون نصًا.',

            'name.max'
                => 'اسم الزبون يجب ألا يتجاوز 100 حرف.',


            'phone.required'
                => 'رقم هاتف الزبون مطلوب.',

            'phone.string'
                => 'رقم هاتف الزبون غير صالح.',

            'phone.regex'
                => 'رقم هاتف الزبون يجب أن يكون بالصيغة الدولية الصحيحة.',

            'phone.unique'
                => 'يوجد زبون مسجل بهذا الرقم مسبقًا.',


            'credit_limit.numeric'
                => 'سقف الدين يجب أن يكون رقمًا.',

            'credit_limit.min'
                => 'سقف الدين يجب ألا يكون أقل من صفر.',

            'credit_limit.max'
                => 'قيمة سقف الدين أكبر من الحد المسموح.',


            'notes.string'
                => 'الملاحظات يجب أن تكون نصًا.',

            'notes.max'
                => 'الملاحظات يجب ألا تتجاوز 1000 حرف.',
        ];
    }
}