<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\Company\ManagementHierarchy\Models\UserCanAccessManagementHierarchy;
use Modules\Company\ManagementHierarchy\DTO\AssignUsersToManagementHierarchyDTO;
use Modules\User\Models\User;

/**
 * @property UserCanAccessManagementHierarchy $model
 * @method UserCanAccessManagementHierarchy findOneOrFail($id)
 * @method UserCanAccessManagementHierarchy findOneByOrFail(array $data)
 */
class UserCanAccessManagementHierarchyRepository extends BaseRepository
{
    public function __construct(UserCanAccessManagementHierarchy $model)
    {
        parent::__construct($model);
    }

    /**
     * Assign users to a management hierarchy (branch)
     */
    public function assignUsersToManagementHierarchy(AssignUsersToManagementHierarchyDTO $dto)
    {
        return DB::transaction(function () use ($dto) {
            // First, remove existing assignments for this branch
            $this->model->where('management_hierarchy_id', $dto->getBranchId())->delete();

            $assignments = collect();

            // Create new assignments
            foreach ($dto->getUserIds() as $userId) {
                $assignment = $this->model->create([
                    'user_id' => $userId,
                    'management_hierarchy_id' => $dto->getBranchId(),
                ]);

                $assignments->push($assignment->load(['user', 'managementHierarchy']));
            }

            return $assignments;
        });
    }

    /**
     * Get users assigned to a specific management hierarchy
     */
    public function getUsersByManagementHierarchy(int $managementHierarchyId): Collection
    {
        $userIds=  $this->model
            ->where('management_hierarchy_id', $managementHierarchyId)

            ->pluck("user_id")->toArray();

        return User::query()->whereIn("id", $userIds)->get();
    }

    /**
     * Get management hierarchies accessible by a specific user
     */
    public function getManagementHierarchiesByUser(string $userId): Collection
    {
        $ids =  $this->model
            ->where('user_id', $userId)
            ->with(['user', 'managementHierarchy'])
            ->pluck("management_hierarchy_id")->toArray();
        $user = User::find($userId);
        return ManagementHierarchy::query()->whereIn("id", $ids)->orWhere("id",$user?->professionalData?->branch_id )->get();
    }

    /**
     * Remove specific user from management hierarchy
     */
    public function removeUserFromManagementHierarchy(string $userId, int $managementHierarchyId): bool
    {
        return $this->model
            ->where('user_id', $userId)
            ->where('management_hierarchy_id', $managementHierarchyId)
            ->delete() > 0;
    }

    /**
     * Check if user has access to management hierarchy
     */
    public function userHasAccess(string $userId, int $managementHierarchyId): bool
    {
        return $this->model
            ->where('user_id', $userId)
            ->where('management_hierarchy_id', $managementHierarchyId)
            ->exists();
    }

    /**
     * Get all user access assignments with pagination
     */
    public function getAllWithPagination(?int $page = null, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }
}
