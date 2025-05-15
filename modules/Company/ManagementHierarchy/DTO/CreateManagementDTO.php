<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\DTO;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class CreateManagementDTO
{
    public function __construct(
        public string        $name,
        public int          $managementId,
        public int           $branchId,
        public UuidInterface $companyId,
        public string        $description,
        public int           $isActive,
        public ?array         $deputyManagerIds,
        public ?UuidInterface $referenceUserId,
        public ?UuidInterface $managerId,


    )
    {
    }

    public function managementToArray(): array
    {
        return [
            'name' => $this->name,
            'parent_id' => $this->managementId ?? $this->branchId,
            'is_main' => $this->managementId == null ? 1 : 0,
            'company_id' => $this->companyId,
            'is_active' => $this->isActive,
            "type" => "management",
            "manager_id" => $this->managerId
        ];
    }

    public function managementDetailToArray(): array
    {
        return [
            'description' => $this->description,
            "reference_user_id" => $this->referenceUserId,
        ];
    }

    public function getDeputyManagerIds(): ?array
    {

        return $this->deputyManagerIds;
    }


}
