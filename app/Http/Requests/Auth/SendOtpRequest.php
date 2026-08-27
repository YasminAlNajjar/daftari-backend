<?php

namespace App\Http\Requests\Auth;

use App\Helpers\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SendOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email',
                'max:150',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.email' => 'يرجى إدخال بريد إلكتروني صالح.',
            'email.max' => 'البريد الإلكتروني يجب ألا يتجاوز 150 حرفًا.',
        ];
    }

    protected function failedValidation(Validator $validator): void {
        throw new HttpResponseException(
            ApiResponse::error(
                'البيانات المدخلة غير صالحة.',
                'VALIDATION_ERROR',
                422,
                $validator->errors()->toArray()
            )
        );
    }
}