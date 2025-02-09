<?php

namespace Modules\CompanyUser\Rules;

use Illuminate\Contracts\Validation\Rule;

class UserNameValidation implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        if(!preg_match("/\p{Arabic}/u", $value) ||count(explode(" ", trim($value))) <3) return false;
        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __("validation.user-name");
    }
}
