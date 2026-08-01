<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

/**
 * 注意：Laravel 10 引入的 Illuminate\Contracts\Validation\ValidationRule
 * （validate() + Closure $fail）在 Laravel 8 中不存在；
 * 且其签名里的 mixed 类型声明属于 PHP 8.0 语法，PHP 7.4 无法解析。
 * 这里改用 Laravel 8 的 Rule 接口（passes() + message()）。
 */
class StrongPassword implements Rule
{
    /** @var string 最近一次校验失败的原因 */
    private $failMessage = '';

    /**
     * Validate that the password meets complexity requirements:
     * - At least 8 characters
     * - Contains at least 3 of 4 character classes:
     *   uppercase, lowercase, digit, special character
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $value = (string) $value;

        if (mb_strlen($value) < 8) {
            $this->failMessage = __('data_security.password_min_length', ['min' => 8]);
            return false;
        }

        $classes = 0;
        if (preg_match('/[A-Z]/', $value)) $classes++;
        if (preg_match('/[a-z]/', $value)) $classes++;
        if (preg_match('/[0-9]/', $value)) $classes++;
        if (preg_match('/[^A-Za-z0-9]/', $value)) $classes++;

        if ($classes < 3) {
            $this->failMessage = __('data_security.password_complexity');
            return false;
        }

        return true;
    }

    /**
     * @return string
     */
    public function message()
    {
        return $this->failMessage;
    }
}
