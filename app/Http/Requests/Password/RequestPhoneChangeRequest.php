<?php

namespace App\Http\Requests\Password;

use App\Http\Requests\BaseRequest;
use App\Rules\PhoneNumberRule;

class RequestPhoneChangeRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => 'required|string|min:8',
            'new_phone' => ['required', 'string', new PhoneNumberRule()],
        ];
    }

    protected function customMessages(): array
    {
        return [
            'new_phone.unique' => 'هذا الرقم مستخدم بالفعل',
        ];
    }

    public function attributes(): array
    {
        return [
            'current_password' => 'كلمة المرور الحالية',
            'new_phone' => 'رقم الهاتف الجديد',
        ];
    }
}
