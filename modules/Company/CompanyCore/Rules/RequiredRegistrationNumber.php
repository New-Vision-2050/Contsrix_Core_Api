<?php

namespace Modules\Company\CompanyCore\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

use Illuminate\Contracts\Validation\DataAwareRule;

class RequiredRegistrationNumber implements Rule, DataAwareRule
{
    protected array $data = [];

    public function setData(array $data)
    {
        $this->data = $data;
    }

    public function passes($attribute, $value): bool
    {
        $registrationTypeId = $this->data['registration_type_id'] ?? null;

        if (!$registrationTypeId) {
            return true;
        }

        $type = DB::table('company_registration_types')
            ->where('id', $registrationTypeId)
            ->value('type');

        $required = (int)$type !== 3;

        return !$required || !empty($value);
    }

    public function message(): string
    {
        return __('validation.company_legal.regestration_number_required');
    }
}
