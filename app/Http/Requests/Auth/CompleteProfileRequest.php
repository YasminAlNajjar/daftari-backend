<?php

namespace App\Http\Requests\Auth;

use App\Helpers\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CompleteProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
            ],

            'email' => [
                'required',
                'email',
                'max:150',
            ],

            'address' => [
                'required',
                'string',
                'max:255',
            ],

            'business_activity_type' => [
                'required',
                'string',
                'max:100',
            ],

            'onboarding_token' => [
                'required',
                'string',
            
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'الاسم مطلوب.',
            'name.string' => 'الاسم غير صالح.',
            'name.max' => 'الاسم يجب ألا يتجاوز 100 حرف.',

            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.email' => 'البريد الإلكتروني بصيغة غير صحيحة.',
            'email.max' => 'البريد الإلكتروني يجب ألا يتجاوز 150 حرفًا.',

            'address.required' => 'العنوان مطلوب.',
            'address.string' => 'العنوان غير صالح.',
            'address.max' => 'العنوان يجب ألا يتجاوز 255 حرفًا.',

            'business_activity_type.required' => 'نوع النشاط التجاري مطلوب.',
            'business_activity_type.string' => 'نوع النشاط التجاري غير صالح.',
            'business_activity_type.max' => 'نوع النشاط التجاري يجب ألا يتجاوز 100 حرف.',

            'onboarding_token.required'=> 'رمز إكمال الملف الشخصي مطلوب.',
            'onboarding_token.string'=> 'رمز إكمال الملف الشخصي غير صالح.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
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