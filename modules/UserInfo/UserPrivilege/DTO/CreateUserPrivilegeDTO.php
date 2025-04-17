<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserPrivilege\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateUserPrivilegeDTO
{
    public function __construct(
        public string $company_id,
        public string $global_id,
        public ?string $type_privilege,
        public ?string $type_allowance,
        public ?string $charge_amount,
        public ?string $description,
        public ?string $privilege_id,
        public ?string $period,
    ) {
    }

    public function toArray(): array
    {
        return [
            'company_id' => $this->company_id,
            'global_id' => $this->global_id,
            'type_privilege' => $this->type_privilege,
            'type_allowance' => $this->type_allowance,
            'charge_amount' => $this->charge_amount,
            'description' => $this->description,
            'privilege_id' => $this->privilege_id,
            'period' => $this->period,
        ];
    }
}
