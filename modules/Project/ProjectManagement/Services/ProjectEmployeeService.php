<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Services;

use Modules\Project\ProjectManagement\Repositories\ProjectEmployeeRepository;
use Modules\Project\ProjectManagement\Models\ProjectEmployee;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectManagement\Models\ProjectRole;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

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

    public function getProjectEmployees(string $projectId, ?string $companyId = null, ?int $perPage = null, ?int $page = null): Collection|LengthAwarePaginator
    {
        $project = ProjectManagement::findOrFail($projectId);

        return $this->repository->getByProject($projectId, $companyId, $perPage, $page);
    }

    public function getEmployeesByContractualEngagement(string $code, ?string $companyId = null): Collection
    {
        return $this->repository->getByContractualEngagement($code, $companyId);
    }

    public function removeEmployeeFromProject(string $contractEmployeeId): bool
    {
        $contractEmployee = $this->repository->findOneOrFail($contractEmployeeId);

        $project = ProjectManagement::withoutGlobalScope('shareable')
            ->where('id', $contractEmployee->project_id)
            ->where('company_id', tenant('id'))
            ->firstOrFail();

        $mandatoryReason = $this->getMandatoryReason(
            userId: (string) $contractEmployee->user_id,
            project: $project
        );

        if ($mandatoryReason !== null) {
            throw new UnprocessableEntityHttpException(
                'Cannot remove mandatory project employee: '.$mandatoryReason
            );
        }

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

    public function assignRoleToEmployee(string $projectEmployeeId, string $projectRoleId): ProjectEmployee
    {
        $projectEmployee = $this->repository->findOneOrFail($projectEmployeeId);

        ProjectManagement::withoutGlobalScope('shareable')
            ->where('id', $projectEmployee->project_id)
            ->where('company_id', tenant('id'))
            ->firstOrFail();

        $roleBelongsToProject = ProjectRole::query()
            ->where('id', $projectRoleId)
            ->where('project_id', $projectEmployee->project_id)
            ->exists();

        if (! $roleBelongsToProject) {
            throw new UnprocessableEntityHttpException('Project role does not belong to this project');
        }

        $this->repository->update($projectEmployeeId, [
            'project_role_id' => $projectRoleId,
        ]);

        return $this->repository->findOneWithRelationsOrFail($projectEmployeeId, [
            'user',
            'assignedBy',
            'projectRole',
            'company',
            'project',
        ]);
    }

    private function getMandatoryReason(string $userId, ProjectManagement $project): ?string
    {
        if ($project->manager_id && $userId === (string) $project->manager_id) {
            return 'project_manager';
        }

        if ($project->created_by_user_id && $userId === (string) $project->created_by_user_id) {
            return 'project_creator';
        }

        return null;
    }
}
