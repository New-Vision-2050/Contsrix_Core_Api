<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Rules;

use Carbon\Carbon;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\Rule;

class ValidateStartDateWithMinimumDays implements Rule, DataAwareRule
{
    protected array $data = [];
    protected int $index;
    protected int $minimumDays;

    public function __construct(int $index, int $minimumDays = 8)
    {
        $this->index = $index;
        $this->minimumDays = $minimumDays;
    }

    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    public function passes($attribute, $value): bool
    {
        if (empty($value)) {
            return true; // Nullable field
        }

        $endDate = $this->data['data'][$this->index]['end_date'] ?? null;
        
        if (!$endDate) {
            return true; // No end date to compare
        }

        try {
            $startDate = Carbon::parse($value);
            $endDateCarbon = Carbon::parse($endDate);

            // Check if start_date is before or equal to end_date
            if ($startDate->greaterThan($endDateCarbon)) {
                return false;
            }

            // Check minimum days difference
            $daysDifference = $startDate->diffInDays($endDateCarbon);
            
            return $daysDifference >= $this->minimumDays;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function message(): string
    {
        return __('validation.company_legal.start_date_minimum_eight_days');
    }
}
