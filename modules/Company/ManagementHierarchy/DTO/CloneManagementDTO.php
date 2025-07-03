<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\DTO;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class CloneManagementDTO
{
    public function __construct(
        public int        $taregtId,
        public int        $sourceId,
        public UuidInterface $companyId,
        public ?array         $deputyManagerIds,
        public ?UuidInterface $referenceUserId,
        public ?UuidInterface $managerId,


    )
    {
    }

    public function managementToArray(): array
    {
        return [
//            'name' => $this->name,
            'parent_id' => $this->taregtId,
            'is_main' => 0,
            'company_id' => $this->companyId,
            'is_active' => 1,
            "manager_id" => $this->managerId
        ];
    }

    public function managementDetailToArray(): array
    {
        return [
            'description' => "",
            "reference_user_id" => $this->referenceUserId,
            "reference_department_id" => $this->sourceId,
            "is_copied" => 1,
//            "branch_id" => $this->branchId
        ];
    }

    public function getDeputyManagerIds(): ?array
    {

        return $this->deputyManagerIds;
    }


}
