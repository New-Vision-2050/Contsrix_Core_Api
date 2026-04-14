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

    public function assignEmployeesToProject(string $projectId, array $userIds): Collection
    {
        $project = ProjectManagement
            ::where('id', $projectId)
//            ->where('company_id', tenant('id'))
            ->firstOrFail();

        $this->repository->syncEmployees(
            projectId: $projectId,
            userIds: $userIds,
            companyId: (string) tenant('id'),
            assignedByUserId: Auth::id() ? (string) Auth::id() : null
        );

        return $this->repository->getByProject($projectId);
    }

    public function appendEmployeesToProject(string $projectId, array $userIds): Collection
    {
        $project = ProjectManagement
            ::where('id', $projectId)
            ->firstOrFail();

        $this->repository->appendEmployees(
            projectId: $projectId,
            userIds: $userIds,
            companyId: (string) tenant('id'),
            assignedByUserId: Auth::id() ? (string) Auth::id() : null
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

    public function getEmployeesNotInProject(string $projectId): Collection
    {
        return $this->repository->getEmployeesNotInProject(
            projectId: $projectId,
            companyId: (string) tenant('id')
        );
    }
}
