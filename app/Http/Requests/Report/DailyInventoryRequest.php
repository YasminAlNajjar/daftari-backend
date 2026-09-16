<?php

namespace App\Http\Requests\Report;

use Illuminate\Foundation\Http\FormRequest;

class DailyInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => [
                'required',
                'date_format:Y-m-d',
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
            'date.required' =>
                'التاريخ مطلوب.',

            'date.date_format' =>
                'صيغة التاريخ غير صالحة. يجب أن تكون Y-m-d.',

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