<?php

declare(strict_types=1);

namespace Modules\Leave\PublicHoliday\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MonthDay implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || !preg_match('/^([0-9]{2})-([0-9]{2})$/', $value, $parts)
            || !checkdate((int) $parts[1], (int) $parts[2], 2000)) {
            $fail(__('leave.public_holiday.month_day'));
        }
    }
}
