<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateManagementCommand
{
    public function __construct(
        private int $id,
        private string $name,
        private int $branchId,
        private int $managementId,
        private UuidInterface $companyId,
        private string $description,
        private int $isActive,
        private array $deputyManagerIds,
        private UuidInterface $referenceUserId,
        private UuidInterface $managerId,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
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
            "branch_id" => $this->branchId
        ];
    }

    public function getDeputyManagerIds(): array
    {

        return $this->deputyManagerIds;
    }

}
