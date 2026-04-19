<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseRequest;
use App\Rules\PhoneNumberRule;

class LoginRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', new PhoneNumberRule()],
            'password' => ['required', 'string', 'min:8'],
            'fcm_token' => ['nullable', 'string'],
        ];
    }

    protected function customMessages(): array
    {
        return [
            'password.min' => 'كلمة المرور يجب أن تحتوي على الأقل :min خانات',
        ];
    }

    public function attributes(): array
    {
        return [
            'phone' => 'رقم الهاتف',
            'password' => 'كلمة المرور',
            'fcm_token' => 'رمز الإشعارات',
        ];
    }
}
