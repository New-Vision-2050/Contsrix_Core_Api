<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\DTO;

use Ramsey\Uuid\UuidInterface;

class UpdateManagementWithRelationsDTO
{
    public function __construct(
        public int            $managementId,
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

    public function getDeputyManagerIds(): array
    {
        return $this->deputyManagerIds;
    }

    public function getManagementId(): int
    {
        return $this->managementId;
    }
}
