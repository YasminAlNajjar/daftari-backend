<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Helpers\ApiResponse;
use Illuminate\Validation\Rule;
use Illuminate\Http\Exceptions\HttpResponseException;
class UpdatePhoneRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
    
    protected function prepareForValidation(): void
    {
        if($this->has('phone'))
            {
                $phone = trim((string) $this->input('phone'));
                  
                if(str_starts_with($phone,"+"))
                    {
                        $phone = "+" . (preg_replace('/\D+/','',substr($phone, 1)));
                    }

                $this->merge([
                    'phone' => $phone,
                ]);
            }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'phone' =>[
                'required',
                'string',
                'regex:/^\+(?:970|972)(?:59|56)',
                Rule::unique('users','phone') -> ignore($this->user()->id),
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'رقم الهاتف مطلوب.',
            'phone.string' => 'رقم الهاتف يجب أن يكون نصًا.',
            'phone.regex' =>       'رقم الهاتف يجب أن يكون بصيغة دولية مثل +970599123456.',
            'phone.unique' => 'رقم الهاتف مستخدم في حساب أخر.',
        ];
    }

    protected function failedValidation($validator)
    {
        throw new HttpResponseException(
            ApiResponse::error(
                'البيانات المدخلة غير صحيحة.' ,
                'VALIDATION_ERROR',
                422,
                $validator->errors()->toArray()
            )
        );
    }


}
