<?php

namespace App\Http\Requests\Report;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerBalancesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
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
                'max:10',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'filter.in' =>
                'فلتر الأرصدة غير صالح.',

            'sort.in' =>
                'طريقة الترتيب غير صالحة.',

            'page.integer' =>
                'رقم الصفحة يجب أن يكون رقمًا صحيحًا.',

            'page.min' =>
                'رقم الصفحة يجب أن يكون 1 أو أكثر.',

            'per_page.integer' =>
                'عدد النتائج في الصفحة يجب أن يكون رقمًا صحيحًا.',

            'per_page.min' =>
                'عدد النتائج في الصفحة يجب أن يكون 1 أو أكثر.',

            'per_page.max' =>
                'عدد النتائج في الصفحة لا يمكن أن يتجاوز 10.',
        ];
    }
}