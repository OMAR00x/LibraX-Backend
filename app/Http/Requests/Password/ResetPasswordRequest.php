<?php

namespace App\Http\Requests\Password;

use App\Http\Requests\BaseRequest;
use App\Rules\PhoneNumberRule;

class ResetPasswordRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', new PhoneNumberRule()],
            'password' => 'required|string|min:8|confirmed',
        ];
    }

    public function attributes(): array
    {
        return [
            'phone' => 'رقم الهاتف',
            'password' => 'كلمة المرور',
        ];
    }
}
