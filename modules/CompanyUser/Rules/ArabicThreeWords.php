<?php

namespace Modules\CompanyUser\Rules;

use Illuminate\Contracts\Validation\Rule;

class ArabicThreeWords implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (!is_string($value)) {
            return false;
        }

        // Check if the string contains only Arabic letters, numbers, and spaces.
        if (!preg_match('/^[\p{Arabic}\s\d]+$/u', $value)) {
            return false;
        }

        // Check if the string has at least three words.
        $words = preg_split('/\s+/', trim($value));
        return count($words) >= 3;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute must be at least three words in Arabic.';
    }
}
