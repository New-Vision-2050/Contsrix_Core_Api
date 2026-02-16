<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserPrivilege\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateUserPrivilegeDTO
{
    public function __construct(
        public string $company_id,
        public string $global_id,
        public ?string $type_privilege_id,
        public ?string $type_allowance_code,
        public ?string $charge_amount,
        public ?string $description,
        public ?string $privilege_id,
        public ?string $period_id,
        public ?string $medical_insurance_id = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'company_id' => $this->company_id,
            'global_id' => $this->global_id,
            'type_privilege_id' => $this->type_privilege_id,
            'type_allowance_code' => $this->type_allowance_code,
            'charge_amount' => $this->charge_amount,
            'description' => $this->description,
            'privilege_id' => $this->privilege_id,
            'period_id' => $this->period_id,
            'medical_insurance_id' => $this->medical_insurance_id,
        ];
    }
}
