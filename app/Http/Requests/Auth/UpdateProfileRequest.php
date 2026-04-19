<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseRequest;

class UpdateProfileRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => 'sometimes|string|max:255|regex:/^[\p{Arabic}\s]+$/u',
            'last_name' => 'sometimes|string|max:255|regex:/^[\p{Arabic}\s]+$/u',
            'avatar' => 'sometimes|url|max:500',
            'password' => 'sometimes|string|min:8|confirmed',
        ];
    }

    protected function customMessages(): array
    {
        return [
            'first_name.regex' => 'الاسم الأول يجب أن يحتوي على حروف عربية فقط',
            'last_name.regex' => 'الاسم الأخير يجب أن يحتوي على حروف عربية فقط',
            'password.min' => 'كلمة المرور يجب أن تحتوي على الأقل :min خانات',
            'password.confirmed' => 'كلمة المرور غير متطابقة',
        ];
    }

    public function attributes(): array
    {
        return [
            'first_name' => 'الاسم الأول',
            'last_name' => 'الاسم الأخير',
            'avatar' => 'الصورة الشخصية',
            'password' => 'كلمة المرور',
        ];
    }
}
