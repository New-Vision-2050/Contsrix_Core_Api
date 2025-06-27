<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\DTO;

class CloneDepartmentDTO
{
    public function __construct(
        public int|string $sourceDepartmentId,
        public int|string|null $targetBranchId = null,
        public ?string $targetParentId = null,
        public bool $cloneSubDepartments = true,
        public bool $cloneManagers = true
    ) {
        // Either targetBranchId or targetParentId must be provided
        if ($targetBranchId === null && $targetParentId === null) {
            throw new \InvalidArgumentException('Either target branch ID or target parent ID must be provided');
        }
    }

    public function toArray(): array
    {
        return [
            'source_department_id' => $this->sourceDepartmentId,
            'target_branch_id' => $this->targetBranchId,
            'target_parent_id' => $this->targetParentId,
            'clone_sub_departments' => $this->cloneSubDepartments,
            'clone_managers' => $this->cloneManagers,
        ];
    }
}
