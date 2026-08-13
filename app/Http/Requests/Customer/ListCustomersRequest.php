<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class ListCustomersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
                'max:50',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'search.string' => 'قيمة البحث يجب أن تكون نصًا.',
            'search.max' => 'قيمة البحث يجب ألا تتجاوز 100 حرف.',

            'page.integer' => 'رقم الصفحة يجب أن يكون رقمًا صحيحًا.',
            'page.min' => 'رقم الصفحة يجب أن يبدأ من 1.',

            'per_page.integer' => 'عدد الزبائن في الصفحة يجب أن يكون رقمًا صحيحًا.',
            'per_page.min' => 'عدد الزبائن في الصفحة يجب ألا يقل عن 1.',
            'per_page.max' => 'عدد الزبائن في الصفحة يجب ألا يتجاوز 50.',
        ];
    }
}