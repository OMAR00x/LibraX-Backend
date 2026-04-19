<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PhoneNumberRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!preg_match('/^09[0-9]{8}$/', $value)) {
            $fail('رقم الهاتف يجب أن يبدأ بـ 09 ويحتوي على 10 أرقام فقط');
        }
    }
}
