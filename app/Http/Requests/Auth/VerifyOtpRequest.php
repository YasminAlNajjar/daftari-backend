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
            'phone' => [
                'required',
                'string',
                'regex:/^\+[1-9]\d{7,14}$/',
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
            'phone.required' => 'PHONE_REQUIRED',
            'phone.string' => 'INVALID_PHONE',
            'phone.regex' => 'INVALID_PHONE',

            'code.required' => 'OTP_CODE_REQUIRED',
            'code.string' => 'INVALID_OTP_FORMAT',
            'code.digits' => 'INVALID_OTP_FORMAT',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        $validationCode = $validator->errors()->first();

        [$message, $errorCode] = match ($validationCode) {
            'PHONE_REQUIRED' => [
                'رقم الهاتف مطلوب.',
                'PHONE_REQUIRED',
            ],

            'INVALID_PHONE' => [
                'رقم الهاتف بصيغة غير صحيحة.',
                'INVALID_PHONE',
            ],

            'OTP_CODE_REQUIRED' => [
                'رمز التحقق مطلوب.',
                'OTP_CODE_REQUIRED',
            ],

            'INVALID_OTP_FORMAT' => [
                'رمز التحقق يجب أن يتكون من 6 أرقام.',
                'INVALID_OTP_FORMAT',
            ],

            default => [
                'البيانات المدخلة غير صالحة.',
                'VALIDATION_ERROR',
            ],
        };

        throw new HttpResponseException(
            ApiResponse::error(
                $message,
                $errorCode,
                422
            )
        );
    }
}