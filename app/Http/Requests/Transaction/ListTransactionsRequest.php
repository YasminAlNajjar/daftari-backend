<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;

class ListTransactionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
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
            'page.integer'
                => 'رقم الصفحة يجب أن يكون رقمًا صحيحًا.',

            'page.min'
                => 'رقم الصفحة يجب أن يبدأ من 1.',

            'per_page.integer'
                => 'عدد المعاملات في الصفحة يجب أن يكون رقمًا صحيحًا.',

            'per_page.min'
                => 'عدد المعاملات في الصفحة يجب أن يكون 1 على الأقل.',

            'per_page.max'
                => 'لا يمكن عرض أكثر من 10 معاملات في الصفحة.',
        ];
    }
}