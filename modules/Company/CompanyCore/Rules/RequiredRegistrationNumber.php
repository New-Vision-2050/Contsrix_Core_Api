<?php

namespace App\Rules\Company\CompanyCore\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class RequiredRegistrationNumber implements Rule
{
    protected ?string $registrationTypeId;
    protected bool $required = false;

    public function __construct(?string $registrationTypeId)
    {
        $this->registrationTypeId = $registrationTypeId;
    }

    public function passes($attribute, $value): bool
    {
        if (!$this->registrationTypeId) {
            return true;
        }

        $type = DB::table('company_registration_types')
            ->where('id', $this->registrationTypeId)
            ->value('type');

        $this->required = $type !== 3;

        // If required and value is empty → invalid
        if ($this->required && empty($value)) {
            return false;
        }

        return true;
    }

    public function message(): string
    {
        return __('validation.company_legal.regestration_number_required');
    }
}
