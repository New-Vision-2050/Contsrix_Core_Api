<?php

declare(strict_types=1);

namespace Modules\CompanyUser\DTO;

use Modules\CompanyUser\Enum\CompanyUserRole;
use Modules\SubEntity\Models\SubEntity;
use Ramsey\Uuid\UuidInterface;

class CreateCompanyUserCompanyRoleDTO
{
    public function __construct(

        public UuidInterface $company_id,
        public string        $role,
        public ?string       $subEntityId = null,
        public ?int          $status = 1
    )
    {
    }

    public function getCompanyId()
    {
        return $this->company_id;

    }

    public function getRole()
    {
        return $this->role;
    }

    public function toArray(): array
    {
        if ($this->role == CompanyUserRole::EMPLOYEE->value && $this->subEntityId == null) {
            $this->subEntityId = SubEntity::where("slug", "employees")->first()?->id;

        }
        if ($this->status == null) {
            $this->status = 1;
        }
        return [
            'role' => $this->role,
            "company_id" => $this->company_id,
            "sub_entity_id" => $this->subEntityId,
            "status" => $this->status
        ];
    }
}
