<?php

namespace App\Http\Requests\Password;

use App\Http\Requests\BaseRequest;
use App\Rules\PhoneNumberRule;

class SendPasswordResetOtpRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', new PhoneNumberRule(), 'exists:users,phone'],
        ];
    }

    protected function customMessages(): array
    {
        return [
            'phone.exists' => 'لا يوجد حساب بهذا الرقم',
        ];
    }

    public function attributes(): array
    {
        return [
            'phone' => 'رقم الهاتف',
        ];
    }
}
