<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Rules;

use Illuminate\Contracts\Validation\Rule;
use Carbon\Carbon;

class StartDateMinimumEightDays implements Rule
{
    private ?string $endDate;

    public function __construct(?string $endDate)
    {
        $this->endDate = $endDate;
    }

    public function passes($attribute, $value): bool
    {
        if (!$value || !$this->endDate) {
            return true; // allow nullable check to be handled elsewhere
        }

        $start = Carbon::parse($value);
        $end = Carbon::parse($this->endDate);

        return $start->diffInDays($end, false) >= 8;
    }

    public function message(): string
    {
        return __('validation.company_legal.start_date_less_than_8_days');
    }
}
