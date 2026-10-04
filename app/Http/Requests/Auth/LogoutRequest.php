<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LogoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_token' => [
                'nullable',
                'string',
                'max:512',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'device_token.string' => 'رمز Firebase الخاص بالجهاز غير صالح.',
            'device_token.max' => 'رمز Firebase الخاص بالجهاز أطول من الحد المسموح.',
        ];
    }
}
