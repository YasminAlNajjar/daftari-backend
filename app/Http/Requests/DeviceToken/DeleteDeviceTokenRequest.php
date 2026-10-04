<?php

namespace App\Http\Requests\DeviceToken;

use Illuminate\Foundation\Http\FormRequest;

class DeleteDeviceTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => [
                'required',
                'string',
                'max:512',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required' => 'رمز Firebase الخاص بالجهاز مطلوب.',
            'token.string' => 'رمز Firebase الخاص بالجهاز غير صالح.',
            'token.max' => 'رمز Firebase الخاص بالجهاز أطول من الحد المسموح.',
        ];
    }
}
