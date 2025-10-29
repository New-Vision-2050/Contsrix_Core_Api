<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Commands;

use Modules\SubEntity\Models\SubEntity;
use Ramsey\Uuid\UuidInterface;

class AssignRoleCompanyUserCommand
{
    private $subEntityId;
    public function __construct(
        private UuidInterface $id,
        private UuidInterface $company_id,
        private int $role,
        private ?array $branch_ids
    ) {
       $this->subEntityId =  SubEntity::where("slug","employees")->first()?->id;

    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }


    public function getCompanyId()
    {
        return $this->company_id;
    }

    public function getBranchIds()
    {
        return $this->branch_ids;
    }

    public function getRole()
    {
        return $this->role;
    }



    public function toArray(): array
    {
        return [
            "company_id"=>$this->company_id,
            "role"=>$this->role,
            "sub_entity_id"=>$this->subEntityId
        ];
    }
}
