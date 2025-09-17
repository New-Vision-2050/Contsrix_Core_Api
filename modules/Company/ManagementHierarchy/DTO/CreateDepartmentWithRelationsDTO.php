<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateDepartmentWithRelationsDTO
{
    public function __construct(
        public string         $name,
        public ?int           $parentId,
        public UuidInterface  $companyId,
        public int            $isActive,
        public array          $managements,
    )
    {
    }

    public function departmentToArray(): array
    {
        return [
            'name' => $this->name,
            'parent_id' => $this->parentId,
            'is_main' => $this->parentId == null ? 1 : 0,
            'company_id' => $this->companyId,
            'is_active' => $this->isActive,
            'type' => 'department',
        ];
    }


    public function departmentDetailToArray(): array
    {
        return [
            'description' => ""
        ];
    }

    public function getParentId()
    {
        return $this->parentId;

    }



}
