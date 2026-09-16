<?php

namespace App\Http\Requests\Report;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GeneralTransactionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_date' => [
                'required',
                'date_format:Y-m-d',
            ],

            'to_date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:from_date',
            ],

            'type' => [
                'nullable',
                Rule::in([
                    'all',
                    'debt',
                    'payment',
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
            'from_date.required' =>
                'تاريخ البداية مطلوب.',

            'from_date.date_format' =>
                'صيغة تاريخ البداية غير صالحة. يجب أن تكون Y-m-d.',

            'to_date.required' =>
                'تاريخ النهاية مطلوب.',

            'to_date.date_format' =>
                'صيغة تاريخ النهاية غير صالحة. يجب أن تكون Y-m-d.',

            'to_date.after_or_equal' =>
                'تاريخ النهاية يجب أن يكون مساويًا لتاريخ البداية أو بعده.',

            'type.in' =>
                'نوع المعاملة غير صالح.',

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