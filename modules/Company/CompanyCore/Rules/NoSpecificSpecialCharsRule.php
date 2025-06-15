<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Rules;

use Illuminate\Contracts\Validation\Rule;

class NoSpecificSpecialCharsRule implements Rule
{
    public function passes($attribute, $value): bool
    {
        $forbiddenChars = [',', '.', '!', '"', "'", '/', '*', '-', '+', '(', ')', '~'];
        return !str_contains($value, $forbiddenChars);
    }

    public function message(): string
    {
        return 'The :attribute must not contain the following characters: , . ! " \' / * - + ( ) ~';
    }
}
