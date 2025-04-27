<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateDepartmentDTO
{
    public function __construct(
        public string        $name,
        public UuidInterface $managementId,
        public ?UuidInterface $departmentId,
        public UuidInterface $companyId,
        public string        $description,
        public int           $isActive,


    )
    {
    }

    public function departmentToArray(): array
    {
        return [
            'name' => $this->name,
            'parent_id' => $this->departmentId ?? $this->managementId,
            'is_main' => $this->departmentId == null ? 1 : 0,
            'company_id' => $this->companyId,
            'is_active' => $this->isActive,
            "type" => "department"
        ];
    }

    public function departmentDetailToArray(): array
    {
        return [
            'description' => $this->description,
        ];
    }


}
