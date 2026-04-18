<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Services;

use Modules\Project\ProjectManagement\Repositories\ProjectEmployeeRepository;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class ProjectEmployeeService
{
    public function __construct(
        private ProjectEmployeeRepository $repository
    ) {
    }

    public function assignEmployeesToProject(string $projectId, array $userIds, ?string $projectRoleId = null, ?string $companyId = null): Collection
    {
        $project = ProjectManagement::withoutGlobalScopes()
            ->where('id', $projectId)
            ->firstOrFail();

        $targetCompanyId = $companyId ?? (string) tenant('id');

        $this->repository->syncEmployees(
            projectId: $projectId,
            userIds: $userIds,
            companyId: $targetCompanyId,
            assignedByUserId: Auth::id() ? (string) Auth::id() : null,
            projectRoleId: $projectRoleId
        );

        return $this->repository->getByProject($projectId);
    }

    public function appendEmployeesToProject(string $projectId, array $userIds, ?string $projectRoleId = null, ?string $companyId = null): Collection
    {
        $project = ProjectManagement::withoutGlobalScopes()
            ->where('id', $projectId)
            ->firstOrFail();

        $targetCompanyId = $companyId ?? (string) tenant('id');

        $this->repository->appendEmployees(
            projectId: $projectId,
            userIds: $userIds,
            companyId: $targetCompanyId,
            assignedByUserId: Auth::id() ? (string) Auth::id() : null,
            projectRoleId: $projectRoleId
        );

        return $this->repository->getByProject($projectId);
    }

    public function getProjectEmployees(string $projectId): Collection
    {
        $project = ProjectManagement::findOrFail($projectId);

        return $this->repository->getByProject($projectId);
    }

    public function removeEmployeeFromProject(string $contractEmployeeId): bool
    {
        $contractEmployee = $this->repository->findOneOrFail($contractEmployeeId);

        $project = ProjectManagement::withoutGlobalScope('shareable')
            ->where('id', $contractEmployee->project_id)
            ->where('company_id', tenant('id'))
            ->firstOrFail();

        return $this->repository->delete($contractEmployeeId);
    }

    public function getEmployeeProjects(string $userId): Collection
    {
        return $this->repository->getProjectsByEmployee($userId);
    }

    public function getEmployeesNotInProject(string $projectId, ?string $companyId = null): Collection
    {
        return $this->repository->getEmployeesNotInProject(
            projectId: $projectId,
            companyId: $companyId ?? (string) tenant('id')
        );
    }

    public function assignRoleToEmployee(string $projectEmployeeId, string $projectRoleId)
    {
        $projectEmployee = $this->repository->findOneOrFail($projectEmployeeId);

        $updated = $this->repository->update($projectEmployeeId, [
            'project_role_id' => $projectRoleId,
        ]);

        // Reload with relationships
        return $this->repository->findOneOrFail($projectEmployeeId, ['user', 'assignedBy', 'projectRole', 'company']);
    }
}
