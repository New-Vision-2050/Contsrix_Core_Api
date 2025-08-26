<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\DTO;

class AssignUsersToManagementHierarchyDTO
{
    public function __construct(
        public readonly int $branchId,
        public readonly array $userIds
    ) {
    }

    public function getBranchId(): int
    {
        return $this->branchId;
    }

    public function getUserIds(): array
    {
        return $this->userIds;
    }

    public function toArray(): array
    {
        return [
            'branch_id' => $this->branchId,
            'user_ids' => $this->userIds,
        ];
    }
}
