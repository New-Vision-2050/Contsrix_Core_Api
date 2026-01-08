<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Rules;

use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class ValidateRegistrationNumber implements Rule, DataAwareRule
{
    protected array $data = [];
    protected int $index;

    public function __construct(int $index)
    {
        $this->index = $index;
    }

    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    public function passes($attribute, $value): bool
    {
        $registrationTypeId = $this->data['data'][$this->index]['registration_type_id'] ?? null;
        
        if (!$registrationTypeId) {
            return true; // Let required rule handle missing ID
        }

        try {
            $uuid = Uuid::fromString($registrationTypeId);
            
            $type = DB::table('company_registration_types')
                ->where('id', $uuid)
                ->value('type');

            // If type is not 3, value is required
            if ((int) $type !== 3) {
                return !empty($value);
            }

            return true;
        } catch (\Exception $e) {
            return true; // Let exists validation handle invalid UUID
        }
    }

    public function message(): string
    {
        return __('validation.company_legal.regestration_number_required');
    }
}
