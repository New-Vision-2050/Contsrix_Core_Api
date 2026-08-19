<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\User\Models\User;
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

    public function paginatedForUser(int $page = 1, int $perPage = 10, ?User $user = null, array $filters = []): array
    {
        $query = $this->model
            ->with([
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
                'company',
                'contractualEngagement',
                'projectTag',
            ])
            ->orderBy('created_at', 'desc');

        $this->applyFilters($query, $filters);

        if ($user !== null && !$user->hasRole('super-admin')) {
            $userId = $user->id;
            $query->where(function ($q) use ($userId) {
                $q->where('manager_id', $userId)
                  ->orWhereHas('employees', fn ($q2) => $q2->where('user_id', $userId));
            });
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'data'       => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
            ],
        ];
    }

    /**
     * Apply filters to the query builder.
     *
     * Supported filters:
     *  - name (string, LIKE match)
     *  - project_type_id (int, exact match)
     *  - sub_project_type_id (int, exact match)
     *  - sub_sub_project_type_id (int, exact match)
     *  - manager_id (uuid, exact match)
     *  - branch_id (uuid, exact match)
     *  - project_owner_type (string: company|individual, exact match)
     *  - project_owner_id (uuid, exact match)
     *  - contract_id (uuid, exact match)
     *  - client_id (uuid, exact match)
     *  - management_id (uuid, exact match)
     *  - status (int: -1|0|1, exact match)
     */
    public function applyFilters($query, array $filters): void
    {
        $stringFilters = ['name'];
        $exactFilters = [
            'project_type_id',
            'sub_project_type_id',
            'sub_sub_project_type_id',
            'manager_id',
            'branch_id',
            'project_owner_type',
            'project_owner_id',
            'contract_id',
            'client_id',
            'management_id',
            'status',
        ];

        foreach ($stringFilters as $field) {
            if (!empty($filters[$field])) {
                $query->where($field, 'LIKE', '%' . $filters[$field] . '%');
            }
        }

        foreach ($exactFilters as $field) {
            if (isset($filters[$field]) && $filters[$field] !== null && $filters[$field] !== '') {
                $query->where($field, $filters[$field]);
            }
        }
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
            'company',
            'contractualEngagement',
            'projectTag',
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
            'subSubProjectType.maintenanceEmergencySetting',
            'subSubProjectType.contractorSetting',
            'subSubProjectType.constructionSetting',
            'subSubProjectType.safetyTaskSetting',
            'subSubProjectType.projectManagementSetting',
            'subSubProjectType.projectOrderPermitSetting',
            'subSubProjectType.orderPermitSetting',
            'manager',
            'branch',
            'ownerCompany',
            'ownerIndividual',
            'client',
            'costCenterBranch',
            'management',
            'currency',
            'company',
            'shares',
            'contractualEngagement',
            'projectTag',
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
            'company',
            'contractualEngagement',
            'projectTag',
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
