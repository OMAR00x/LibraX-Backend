<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PriceRule implements ValidationRule
{
    public function __construct(
        private float $min = 0,
        private ?float $max = null
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_numeric($value)) {
            $fail('السعر يجب أن يكون رقم');
            return;
        }

        if ($value < $this->min) {
            $fail("السعر يجب أن يكون {$this->min} على الأقل");
        }

        if ($this->max !== null && $value > $this->max) {
            $fail("السعر يجب ألا يتجاوز {$this->max}");
        }
    }
}
