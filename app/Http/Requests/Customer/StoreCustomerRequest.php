<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * توحيد صيغة رقم الهاتف قبل الـ validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('phone')) {
            $phone = trim((string) $this->input('phone'));

            /*
             * نحافظ على + في بداية الرقم،
             * ونحذف المسافات والشرطات والأقواس من باقي الرقم.
             */
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
        return [
            'name' => [
                'required',
                'string',
                'max:100',
            ],

            'phone' => [
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
                    }),
            ],

            'credit_limit' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999.99',
            ],

            'notes' => [
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