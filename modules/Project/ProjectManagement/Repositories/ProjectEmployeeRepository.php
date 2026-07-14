<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Modules\Project\ProjectManagement\Models\ProjectEmployee;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProjectEmployeeRepository extends BaseRepository
{
    public function __construct(ProjectEmployee $model)
    {
        parent::__construct($model);
    }

    public function getByProject(string $projectId, ?string $companyId = null, ?int $perPage = null, ?int $page = null): Collection|LengthAwarePaginator
    {
        $query = $this->buildProjectEmployeeQuery($companyId)
            ->where('project_id', $projectId);

        if ($perPage !== null && $perPage > 0) {
            return $query->paginate($perPage, ['*'], 'page', $page ?? 1);
        }

        return $query->get();
    }

    public function getByContractualEngagement(string $code, ?string $companyId = null): Collection
    {
        $targetCompanyId = $companyId ?? (string) tenant('id');

        $projectIds = ProjectManagement::query()
            ->where('company_id', $targetCompanyId)
            ->whereHas('contractualEngagement', function ($q) use ($code) {
                $q->where('code', $code);
            })
            ->pluck('id')
            ->toArray();

        if (empty($projectIds)) {
            return new Collection();
        }

        return $this->buildProjectEmployeeQuery($companyId)
            ->whereIn('project_id', $projectIds)
            ->get();
    }

    private function buildProjectEmployeeQuery(?string $companyId = null)
    {
        $search = request()->input('search');

        $query = $this->model
            ->with([
                'user.userProfessionalData.attendanceConstraint',
                'assignedBy',
                'projectRole.permissions',
                'company',
                'project',
            ]);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if ($search !== null && $search !== '') {
            $normalizedPhoneSearch = preg_replace('/[\s+]/', '', (string) $search);

            $query->whereHas('user', function ($userQuery) use ($search, $normalizedPhoneSearch) {
                $userQuery
                    ->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('phone', 'LIKE', "%{$search}%")
                    ->orWhere('phone_code', 'LIKE', "%{$search}%")
                    ->orWhereRaw(
                        "REPLACE(REPLACE(CONCAT(COALESCE(phone_code, ''), COALESCE(phone, '')), ' ', ''), '+', '') LIKE ?",
                        ["%{$normalizedPhoneSearch}%"]
                    );
            });
        }

        return $query;
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

    public function isEmployeeAssignedWithoutTenancy(string $projectId, string $userId): bool
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
            if (!$this->isEmployeeAssignedWithoutTenancy($projectId, $userId)) {
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

        return \Modules\User\Models\User::query()->withoutTenancy()->where("company_id",$companyId)
            ->whereHas('companyUserCompanies', function ($query) use ($companyId) {
                $query->withoutTenancy()->where('company_id', $companyId)
                    ->where('role', \Modules\CompanyUser\Enum\CompanyUserRole::EMPLOYEE->value);
            })
            ->whereNotIn('id', $assignedUserIds)
            ->with(['companyUser.jobTitle', 'companyUser.country'])
            ->get();
    }
}
