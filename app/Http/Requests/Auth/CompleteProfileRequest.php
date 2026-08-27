<?php

namespace App\Http\Requests\Auth;

use App\Helpers\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class CompleteProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('phone')) {
            $phone = trim((string) $this->input('phone'));

            if (str_starts_with($phone, '+')) {
                $phone = '+' . (
                    preg_replace(
                        '/\D+/',
                        '',
                        substr($phone, 1)
                    ) ?? ''
                );
            }

            $this->merge([
                'phone' => $phone,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'onboarding_token' => [
                'required',
                'string',
            ],

            'name' => [
                'required',
                'string',
                'max:100',
            ],

            'phone' => [
                'required',
                'string',
                'regex:/^\+970(59|56)\d{7}$/',
                Rule::unique('users', 'phone'),
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
        ];
    }

    public function messages(): array
    {
        return [
            'onboarding_token.required'
                => 'رمز إكمال الملف الشخصي مطلوب.',

            'onboarding_token.string'
                => 'رمز إكمال الملف الشخصي غير صالح.',


            'name.required'
                => 'الاسم مطلوب.',

            'name.string'
                => 'الاسم يجب أن يكون نصًا.',

            'name.max'
                => 'الاسم يجب ألا يتجاوز 100 حرف.',


            'phone.required'
                => 'رقم الهاتف مطلوب.',

            'phone.string'
                => 'رقم الهاتف غير صالح.',

            'phone.regex'
                => 'رقم الهاتف يجب أن يكون رقم موبايل فلسطينيًا صحيحًا بالصيغة الدولية مثل +970599123456.',

            'phone.unique'
                => 'رقم الهاتف مستخدم في حساب آخر.',


            'address.required'
                => 'العنوان مطلوب.',

            'address.string'
                => 'العنوان يجب أن يكون نصًا.',

            'address.max'
                => 'العنوان يجب ألا يتجاوز 255 حرفًا.',


            'business_activity_type.required'
                => 'نوع النشاط التجاري مطلوب.',

            'business_activity_type.string'
                => 'نوع النشاط التجاري يجب أن يكون نصًا.',

            'business_activity_type.max'
                => 'نوع النشاط التجاري يجب ألا يتجاوز 100 حرف.',
        ];
    }

    protected function failedValidation(
        Validator $validator
    ): void {
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