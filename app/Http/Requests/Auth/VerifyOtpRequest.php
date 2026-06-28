<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseRequest;

class VerifyOtpRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'otp' => 'required|string|size:5',
            'phone' => 'required|string|regex:/^09\d{8}$/'
        ];
    }

    protected function customMessages(): array
    {
        return [
            'otp.size' => 'كود التحقق يجب أن يكون 5 أرقام',
            'phone.regex' => 'رقم الجوال يجب أن يبدأ بـ 09 ويتكون من 10 أرقام',
        ];
    }

    public function attributes(): array
    {
        return [
            'otp' => 'كود التحقق',
            'phone' => 'رقم الجوال',
        ];
    }
}
