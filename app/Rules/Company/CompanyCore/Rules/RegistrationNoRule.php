<?php

namespace App\Rules\Company\CompanyCore\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\Company\CompanyCore\Models\Company;

class RegistrationNoRule implements ValidationRule
{
    protected $registrationType;
    protected $registrationTypeId;
    public function __construct($registrationType, $registrationTypeId)
    {
        $this->registrationType = $registrationType;
        $this->registrationTypeId = $registrationTypeId;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->registrationType == 1) {
            // Validate the registration_no for pattern when registration_type is 1
            if (!preg_match('/^(1|700|40)\d+$/', $value)) {
                $fail('The ' . $attribute . ' must follow the required pattern.');
            }
        } elseif ($this->registrationType == 2) {
            // Check if the registration_no is unique within the same registration_type_id
            $exists = Company::where('registration_no', $value)
                ->where('registration_type_id', $this->registrationTypeId)
                ->exists();

            if ($exists) {
                $fail('The ' . $attribute . ' has already been taken for this registration type.');
            }
        }
    }
}
