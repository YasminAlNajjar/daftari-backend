<?php

namespace App\Http\Requests\DeviceToken;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDeviceTokenRequest extends FormRequest
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
            'platform' => [
                'required',
                'string',
                Rule::in(['android', 'ios']),
            ],
            'installation_id' => [
                'nullable',
                'string',
                'max:191',
            ],
            'device_name' => [
                'nullable',
                'string',
                'max:100',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required' => 'رمز Firebase الخاص بالجهاز مطلوب.',
            'token.string' => 'رمز Firebase الخاص بالجهاز غير صالح.',
            'token.max' => 'رمز Firebase الخاص بالجهاز أطول من الحد المسموح.',
            'platform.required' => 'نظام تشغيل الجهاز مطلوب.',
            'platform.string' => 'نظام تشغيل الجهاز غير صالح.',
            'platform.in' => 'نظام تشغيل الجهاز يجب أن يكون Android أو iOS.',
            'installation_id.string' => 'معرّف تثبيت التطبيق غير صالح.',
            'installation_id.max' => 'معرّف تثبيت التطبيق أطول من الحد المسموح.',
            'device_name.string' => 'اسم الجهاز غير صالح.',
            'device_name.max' => 'اسم الجهاز يجب ألا يتجاوز 100 حرف.',
        ];
    }
}
