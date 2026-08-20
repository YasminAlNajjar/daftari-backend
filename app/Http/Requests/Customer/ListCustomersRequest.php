<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class ListCustomersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * تنظيف قيمة البحث قبل التحقق منها.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('search')) {
            $this->merge([
                'search' => trim((string) $this->input('search')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'search' => [
                'nullable',
                'string',
                'max:100',
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
                'max:10',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'search.string'
                => 'قيمة البحث يجب أن تكون نصًا.',

            'search.max'
                => 'قيمة البحث يجب ألا تتجاوز 100 حرف.',

            'page.integer'
                => 'رقم الصفحة يجب أن يكون رقمًا صحيحًا.',

            'page.min'
                => 'رقم الصفحة يجب أن يبدأ من 1.',

            'per_page.integer'
                => 'عدد الزبائن في الصفحة يجب أن يكون رقمًا صحيحًا.',

            'per_page.min'
                => 'عدد الزبائن في الصفحة يجب أن يكون 1 على الأقل.',

            'per_page.max'
                => 'لا يمكن عرض أكثر من 10 زبائن في الصفحة.',
        ];
    }
}