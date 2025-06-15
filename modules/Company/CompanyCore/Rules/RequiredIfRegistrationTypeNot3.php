<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\UuidInterface;

class RequiredIfRegistrationTypeNot3 implements Rule
{
    private UuidInterface  $registrationTypeId;

    public function __construct(?UuidInterface $registrationTypeId)
    {
        $this->registrationTypeId = $registrationTypeId;
    }

    public function passes($attribute, $value): bool
    {
        if (!$this->registrationTypeId) {
            return true; // let 'required' rule handle missing ID
        }

        $type = DB::table('company_registration_types')
            ->where('id', $this->registrationTypeId)
            ->value('type');

        // If type is not 3, value is required
        if ((int) $type !== 3) {
            return !empty($value);
        }

        return true;
    }

    public function message(): string
    {
        return __('validation.company_legal.regestration_number_required');
    }
}
