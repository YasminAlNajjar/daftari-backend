<?php

namespace App\Http\Requests\Auth;

use App\Helpers\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class VerifyOtpRequest extends FormRequest
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

            'code' => [
                'required',
                'string',
                'digits:6',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.email' => 'يرجى إدخال بريد إلكتروني صالح.',
            'email.max' => 'البريد الإلكتروني يجب ألا يتجاوز 150 حرفًا.',

            'code.required' => 'رمز التحقق مطلوب.',
            'code.string' => 'رمز التحقق غير صالح.',
            'code.digits' => 'رمز التحقق يجب أن يتكون من 6 أرقام.',
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