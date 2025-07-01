<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateManagementWithRelationsDTO
{
    public function __construct(
        public string         $name,
        public ?int           $parentId,
        public UuidInterface  $companyId,
        public ?UuidInterface $managerId,
        public ?string        $description,
        public int            $isActive,
        public array          $jobTypes,
        public array          $jobTitles,
        public array          $branches,
        public array          $deputyManagerIds,
    )
    {
    }

    public function managementToArray(): array
    {
        return [
            'name' => $this->name,
            'parent_id' => $this->parentId,
            'is_main' => $this->parentId == null ? 1 : 0,
            'company_id' => $this->companyId,
            'is_active' => $this->isActive,
            'type' => 'management',
            'manager_id' => $this->managerId
        ];
    }

    public function getParentId()
    {
        return $this->parentId;

    }

    public function managementDetailToArray(): array
    {
        return [
            'description' => $this->description,
        ];
    }

    public function getDeputyManagerIds(): array
    {
        return $this->deputyManagerIds;
    }

    public function getJobTypes(): array
    {
        return $this->jobTypes;
    }

    public function getJobTitles(): array
    {
        return $this->jobTitles;
    }

    public function getBranches(): array
    {
        return $this->branches;
    }
}
