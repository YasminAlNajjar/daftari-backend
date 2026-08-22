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
            'phone' => [
                'required',
                'string',
                'regex:/^\+(970|972)\d{9}$/',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'رقم الهاتف مطلوب.',
            'phone.string' => 'رقم الهاتف غير صالح.',
            'phone.regex' => 'رقم الهاتف بصيغة غير صحيحة.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        $errors = $validator->errors();

        $code = $errors->has('phone')
            && in_array('رقم الهاتف مطلوب.', $errors->get('phone'))
                ? 'PHONE_REQUIRED'
                : 'INVALID_PHONE';

        $message = $code === 'PHONE_REQUIRED'
            ? 'رقم الهاتف مطلوب.'
            : 'رقم الهاتف بصيغة غير صحيحة.';

        throw new HttpResponseException(
            ApiResponse::error(
                $message,
                $code,
                422
            )
        );
    }
}