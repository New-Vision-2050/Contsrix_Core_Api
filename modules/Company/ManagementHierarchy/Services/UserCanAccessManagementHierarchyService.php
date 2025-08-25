<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Company\ManagementHierarchy\DTO\AssignUsersToManagementHierarchyDTO;
use Modules\Company\ManagementHierarchy\Models\UserCanAccessManagementHierarchy;
use Modules\Company\ManagementHierarchy\Repositories\UserCanAccessManagementHierarchyRepository;

class UserCanAccessManagementHierarchyService
{
    public function __construct(
        private UserCanAccessManagementHierarchyRepository $repository
    ) {
    }

    /**
     * Assign users to a management hierarchy (branch)
     */
    public function assignUsersToManagementHierarchy(AssignUsersToManagementHierarchyDTO $dto)
    {
        return $this->repository->assignUsersToManagementHierarchy($dto);
    }

    /**
     * Get users assigned to a specific management hierarchy
     */
    public function getUsersByManagementHierarchy(int $managementHierarchyId): Collection
    {
        return $this->repository->getUsersByManagementHierarchy($managementHierarchyId);
    }

    /**
     * Get management hierarchies accessible by a specific user
     */
    public function getManagementHierarchiesByUser(string $userId): Collection
    {
        return $this->repository->getManagementHierarchiesByUser($userId);
    }

    /**
     * Remove specific user from management hierarchy
     */
    public function removeUserFromManagementHierarchy(string $userId, int $managementHierarchyId): bool
    {
        return $this->repository->removeUserFromManagementHierarchy($userId, $managementHierarchyId);
    }

    /**
     * Check if user has access to management hierarchy
     */
    public function userHasAccess(string $userId, int $managementHierarchyId): bool
    {
        return $this->repository->userHasAccess($userId, $managementHierarchyId);
    }

    /**
     * Get all user access assignments with pagination
     */
    public function getAllWithPagination(?int $page = null, ?int $perPage = 10): Collection
    {
        return $this->repository->getAllWithPagination($page, $perPage);
    }
}
