<?php

namespace App\Http\Requests\Password;

use App\Http\Requests\BaseRequest;

class ChangePasswordRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'old_password' => 'required|string|min:8',
            'new_password' => 'required|string|min:8|confirmed',
        ];
    }

    public function attributes(): array
    {
        return [
            'old_password' => 'كلمة المرور القديمة',
            'new_password' => 'كلمة المرور الجديدة',
        ];
    }
}
