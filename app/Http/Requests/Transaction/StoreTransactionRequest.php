<?php

namespace App\Http\Requests\Transaction;

use App\Models\Transaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => [
                'required',
                Rule::in(Transaction::types()),
            ],

            'amount' => [
                'required',
                'numeric',
                'gt:0',
                'max:999999999.99',
            ],

            'transaction_date' => [
                'required',
                'date',
            ],

            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required'
                => 'نوع المعاملة مطلوب.',

            'type.in'
                => 'نوع المعاملة غير صالح.',

            'amount.required'
                => 'مبلغ المعاملة مطلوب.',

            'amount.numeric'
                => 'مبلغ المعاملة يجب أن يكون رقمًا.',

            'amount.gt'
                => 'مبلغ المعاملة يجب أن يكون أكبر من صفر.',

            'amount.max'
                => 'مبلغ المعاملة أكبر من الحد المسموح.',

            'transaction_date.required'
                => 'تاريخ المعاملة مطلوب.',

            'transaction_date.date'
                => 'تاريخ المعاملة غير صالح.',

            'description.string'
                => 'وصف المعاملة يجب أن يكون نصًا.',

            'description.max'
                => 'وصف المعاملة يجب ألا يتجاوز 1000 حرف.',
        ];
    }
}