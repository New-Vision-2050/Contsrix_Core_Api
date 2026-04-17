<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Modules\Project\ProjectManagement\Models\ProjectEmployee;
use Illuminate\Database\Eloquent\Collection;

class ProjectEmployeeRepository extends BaseRepository
{
    public function __construct(ProjectEmployee $model)
    {
        parent::__construct($model);
    }

    public function getByProject(string $projectId): Collection
    {
        return $this->model
            ->where('project_id', $projectId)
            ->with(['user', 'assignedBy', 'projectRole.permissions'])
            ->get();
    }

    public function assignEmployee(array $data): ProjectEmployee
    {
        return $this->create($data);
    }

    public function isEmployeeAssigned(string $projectId, string $userId): bool
    {
        return $this->model
            ->where('project_id', $projectId)
            ->where('user_id', $userId)
            ->exists();
    }

    public function removeEmployee(string $projectId, string $userId): bool
    {
        return $this->model
            ->where('project_id', $projectId)
            ->where('user_id', $userId)
            ->delete() > 0;
    }

    public function syncEmployees(string $projectId, array $userIds, string $companyId, ?string $assignedByUserId = null, ?string $projectRoleId = null): void
    {
        $existingUserIds = $this->model
            ->where('project_id', $projectId)
            ->pluck('user_id')
            ->toArray();

        $toAdd = array_diff($userIds, $existingUserIds);
        $toRemove = array_diff($existingUserIds, $userIds);

        foreach ($toRemove as $userId) {
            $this->removeEmployee($projectId, $userId);
        }

        foreach ($toAdd as $userId) {
            if (!$this->isEmployeeAssigned($projectId, $userId)) {
                $this->assignEmployee([
                    'project_id' => $projectId,
                    'user_id' => $userId,
                    'company_id' => $companyId,
                    'assigned_by_user_id' => $assignedByUserId,
                    'project_role_id' => $projectRoleId,
                ]);
            }
        }
    }

    public function appendEmployees(string $projectId, array $userIds, string $companyId, ?string $assignedByUserId = null, ?string $projectRoleId = null): void
    {
        foreach ($userIds as $userId) {
            if (!$this->isEmployeeAssigned($projectId, $userId)) {
                $this->assignEmployee([
                    'project_id' => $projectId,
                    'user_id' => $userId,
                    'company_id' => $companyId,
                    'assigned_by_user_id' => $assignedByUserId,
                    'project_role_id' => $projectRoleId,
                ]);
            }
        }
    }

    public function getProjectsByEmployee(string $userId): Collection
    {
        return $this->model
            ->where('user_id', $userId)
            ->with('project')
            ->get();
    }

    public function getEmployeesNotInProject(string $projectId, string $companyId): Collection
    {
        $assignedUserIds = $this->model
            ->where('project_id', $projectId)
            ->pluck('user_id')
            ->toArray();

        return \Modules\User\Models\User::query()->withoutTenancy()
            ->whereHas('companyUserCompanies', function ($query) use ($companyId) {
                $query->withoutTenancy()->where('company_id', $companyId)
                    ->where('role', \Modules\CompanyUser\Enum\CompanyUserRole::EMPLOYEE->value);
            })
            ->whereNotIn('id', $assignedUserIds)
            ->with(['companyUser.jobTitle', 'companyUser.country'])
            ->get();
    }
}
