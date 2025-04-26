<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateManagementDTO
{
    public function __construct(
        public string         $name,
        public ?UuidInterface $managementId,
        public UuidInterface  $branchId,
        public UuidInterface  $companyId,
        public string         $description,
        public int            $isActive,


    )
    {
    }

    public function managementToArray(): array
    {
        return [
            'name' => $this->name,
            'parent_id' => $this->managementId ?? $this->branchId,
            'is_main' => $this->managementId == null  ? 1 : 0,
            'company_id' => $this->companyId,
            'is_active' => $this->isActive,
            "type" => "management"
        ];
    }

    public function managementDetailToArray(): array
    {
        return [
            'description' => $this->description,

        ];
    }


}
