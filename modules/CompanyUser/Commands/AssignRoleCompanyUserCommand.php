<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Commands;

use Ramsey\Uuid\UuidInterface;

class AssignRoleCompanyUserCommand
{
    public function __construct(
        private UuidInterface $id,
        private UuidInterface $company_id,
        private int $role
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }


    public function getCompanyId()
    {
        return $this->company_id;
    }



    public function toArray(): array
    {
        return [
            "company_id"=>$this->company_id,
            "role"=>$this->role,
        ];
    }
}
