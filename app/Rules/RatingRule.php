<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class RatingRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_numeric($value) || $value < 1 || $value > 5) {
            $fail('التقييم يجب أن يكون بين 1 و 5');
        }
    }
}
