<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends FormRequest
{
    /**
     * السماح للمستخدم المسجل فقط بتنفيذ الطلب.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * توحيد رقم الهاتف قبل التحقق منه.
     *
     * مثال:
     * +970 59-123-4567
     * يصبح:
     * 970591234567
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

    /**
     * قواعد التحقق عند إضافة عميل.
     *
     * في شاشة الإضافة نحتاج:
     * - name
     * - phone
     *
     * credit_limit وnotes سيظهران لاحقًا في شاشة التعديل.
     */
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
                'regex:/^[0-9]{10}$/',

                // يمنع تكرار الهاتف لدى المستخدم نفسه فقط.
                Rule::unique('customers', 'phone')
                    ->where(
                        fn ($query) => $query->where(
                            'user_id',
                            $this->user()->id
                        )
                    ),
            ],
        ];
    }

    /**
     * رسائل التحقق.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'اسم الزبون مطلوب.',
            'name.string' => 'اسم الزبون يجب أن يكون نصًا.',
            'name.max' => 'اسم الزبون يجب ألا يتجاوز 100 حرف.',

            'phone.required' => 'رقم هاتف الزبون مطلوب.',
            'phone.string' => 'رقم هاتف الزبون يجب أن يكون نصًا.',
            'phone.regex' => 'صيغة رقم هاتف الزبون غير صحيحة.',
            'phone.unique' => 'يوجد زبون مسجل بهذا الرقم مسبقًا.',
        ];
    }
}