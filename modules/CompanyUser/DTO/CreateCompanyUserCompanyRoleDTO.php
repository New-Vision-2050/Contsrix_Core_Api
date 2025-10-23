<?php

declare(strict_types=1);

namespace Modules\CompanyUser\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateCompanyUserCompanyRoleDTO
{
    public function __construct(

        public UuidInterface $company_id,
        public string        $role,
        public ?string       $subEntityId = null
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
        return [
            'role' => $this->role,
            "company_id" => $this->company_id,
            "sub_entity_id"=>$this->subEntityId
        ];
    }
}
