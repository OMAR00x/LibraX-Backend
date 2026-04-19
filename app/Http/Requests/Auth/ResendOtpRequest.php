<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseRequest;
use App\Rules\PhoneNumberRule;

class ResendOtpRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', new PhoneNumberRule()]
        ];
    }

    public function attributes(): array
    {
        return [
            'phone' => 'رقم الهاتف',
        ];
    }
}
