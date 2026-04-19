<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseRequest;
use App\Rules\PhoneNumberRule;

class PreRegisterRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:255|regex:/^[\p{Arabic}\s]+$/u',
            'last_name' => 'required|string|max:255|regex:/^[\p{Arabic}\s]+$/u',
            'phone' => ['required', 'string', new PhoneNumberRule()],
            'password' => 'required|string|min:8|confirmed',
        ];
    }

    protected function customMessages(): array
    {
        return [
            'first_name.regex' => 'الاسم الأول يجب أن يحتوي على حروف عربية فقط',
            'last_name.regex' => 'الاسم الأخير يجب أن يحتوي على حروف عربية فقط',
            'phone.unique' => 'يوجد حساب مسجل بهذا الرقم بالفعل',
        ];
    }

    public function attributes(): array
    {
        return [
            'first_name' => 'الاسم الأول',
            'last_name' => 'الاسم الأخير',
            'phone' => 'رقم الهاتف',
            'password' => 'كلمة المرور',
        ];
    }
}
