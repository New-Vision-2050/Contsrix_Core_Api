<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use App\Traits\HasExport;

/**
 * @property ProjectManagement $model
 * @method ProjectManagement findOneOrFail($id)
 * @method ProjectManagement findOneByOrFail(array $data)
 */
class ProjectManagementRepository extends BaseRepository
{
    use HasExport;

    public function __construct(ProjectManagement $model)
    {
        parent::__construct($model);
    }

    public function getProjectManagementList(?int $page, ?int $perPage = 10): Collection
    {
        $query = $this->model->with([
            'projectType',
            'subProjectType',
            'subSubProjectType',
            'manager',
            'branch',
            'ownerCompany',
            'ownerIndividual',
            'client',
            'costCenterBranch',
            'management',
            'currency',
            'company'
        ]);

        return $this->paginatedList([], $page, $perPage, $query);
    }

    public function getProjectManagement(UuidInterface $id): ProjectManagement
    {
        return $this->model->with([
            'projectType',
            'subProjectType',
            'subSubProjectType.projectDataSetting',
            'subSubProjectType.attachmentContractSetting',
            'subSubProjectType.attachmentTermsContractSetting',
            'subSubProjectType.contractorContractSetting',
            'subSubProjectType.employeeContractSetting',
            'subSubProjectType.departmentContractSetting',
            'subSubProjectType.attachmentCycleSetting',
            'subSubProjectType.archiveLibrarySetting',
            'subSubProjectType.rolesAndPermissionsSetting',
            'subSubProjectType.projectSharingSetting',
            'manager',
            'branch',
            'ownerCompany',
            'ownerIndividual',
            'client',
            'costCenterBranch',
            'management',
            'currency',
            'company',
            'shares'
        ])->findOrFail($id->toString());
    }

    public function createProjectManagement(array $data): ProjectManagement
    {
        $project = $this->create($data);

        return $project->load([
            'projectType',
            'subProjectType',
            'subSubProjectType',
            'manager',
            'branch',
            'ownerCompany',
            'ownerIndividual',
            'client',
            'costCenterBranch',
            'management',
            'currency',
            'company'
        ]);
    }

    public function updateProjectManagement(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteProjectManagement(UuidInterface $id): bool
    {
        $project = $this->find($id);

        if (!$project) {
            throw new \Exception(__('validation.project-not-found'), 404);
        }

        // Check for related employees
        $employeesCount = $project->projectEmployees()->count();
        if ($employeesCount > 0) {
            throw new \Exception(__('validation.cannot_delete_project_has_employees', ['count' => $employeesCount]), 422);
        }

        // Check for related roles
        $rolesCount = $project->projectRoles()->count();
        if ($rolesCount > 0) {
            throw new \Exception(__('validation.cannot_delete_project_has_roles', ['count' => $rolesCount]), 422);
        }

        return $this->delete($id);
    }

    /**
     * Get total projects count for a company up to a specific date
     */
    public function getTotalProjectsCount(string $companyId, $endDate): int
    {
        return $this->model
            ->where('company_id', $companyId)
            ->where('created_at', '<=', $endDate)
            ->count();
    }

    /**
     * Get total projects value for a company up to a specific date
     */
    public function getTotalProjectsValue(string $companyId, $endDate): float
    {
        return (float) $this->model
            ->where('company_id', $companyId)
            ->where('created_at', '<=', $endDate)
            ->sum('project_value');
    }

    /**
     * Get active projects count for a company up to a specific date
     */
    public function getActiveProjectsCount(string $companyId, $endDate): int
    {
        return $this->model
            ->where('company_id', $companyId)
            ->where('created_at', '<=', $endDate)
            ->where('status', 1)
            ->count();
    }

    /**
     * Get inactive projects count for a company up to a specific date
     */
    public function getInactiveProjectsCount(string $companyId, $endDate): int
    {
        return $this->model
            ->where('company_id', $companyId)
            ->where('created_at', '<=', $endDate)
            ->where('status', 0)
            ->count();
    }
}
