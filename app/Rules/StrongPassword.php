<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StrongPassword implements ValidationRule
{
    /**
     * Validate that the password meets complexity requirements:
     * - At least 8 characters
     * - Contains at least 3 of 4 character classes:
     *   uppercase, lowercase, digit, special character
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (mb_strlen($value) < 8) {
            $fail(__('data_security.password_min_length', ['min' => 8]));
            return;
        }

        $classes = 0;
        if (preg_match('/[A-Z]/', $value)) $classes++;
        if (preg_match('/[a-z]/', $value)) $classes++;
        if (preg_match('/[0-9]/', $value)) $classes++;
        if (preg_match('/[^A-Za-z0-9]/', $value)) $classes++;

        if ($classes < 3) {
            $fail(__('data_security.password_complexity'));
        }
    }
}
