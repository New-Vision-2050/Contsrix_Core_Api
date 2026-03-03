<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Repositories;

use App\Scopes\CustomTenantScope;
use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Modules\Project\ProjectType\Models\ProjectType;
use App\Traits\HasExport;

/**
 * @property ProjectType $model
 * @method ProjectType findOneOrFail($id)
 * @method ProjectType findOneByOrFail(array $data)
 */
class ProjectTypeRepository extends BaseRepository
{
    use HasExport;

    public function __construct(ProjectType $model)
    {
        parent::__construct($model);
    }

    public function getProjectTypeList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getProjectType(int $id): ProjectType
    {
        return $this->model->with([
            'projectDataSetting',
            'attachmentContractSetting',
            'attachmentTermsContractSetting',
            'contractorContractSetting',
            'employeeContractSetting',
            'departmentContractSetting',
            'parent',
            'children'
        ])->findOrFail($id);
    }

    public function createProjectType(array $data): ProjectType
    {
        return $this->create($data);
    }

    public function updateProjectType(int $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteProjectType(int $id): bool
    {
        return $this->delete($id);
    }

    public function getDirectChildren(int $parentId): Collection
    {
        return $this->model->where('parent_id', $parentId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function getRootProjectTypes(): Collection
    {
        return $this->model->whereNull('parent_id')
            ->where('is_active', true)
            ->get();
    }

    public function getProjectTypeWithChildren(int $id): ProjectType
    {
        return $this->model->with(['children' => function ($query) {
            $query->where('is_active', true)->orderBy('name');
        }])->findOrFail($id);
    }

    public function getProjectTypeWithSchemas(int $id): ProjectType
    {
        return $this->model->with(['activeSchemas'])->findOrFail($id);
    }

    public function getSeededProjectTypes(): Collection
    {
        return $this->model->seeded()
            ->where('is_active', true)
            ->orderBy('path')
            ->get();
    }

    public function getUserCreatedProjectTypes(): Collection
    {
        return $this->model->userCreated()
            ->where('is_active', true)
            ->orderBy('path')
            ->get();
    }

    public function getSecondLevelProjectTypes(): Collection
    {
        return $this->model->whereHas('parent', function ($query) {
            $query->whereNull('parent_id');
        })
            ->where('is_active', true)
            ->where('is_have_schema', true)
            ->orderBy('name')
            ->get();
    }

    public function createSecondLevelProjectType(array $data, array $schemaIds): ProjectType
    {
        // Load parent if parent_id is provided to prevent AsTree trait errors
        if (!empty($data['parent_id'])) {
            $parent = $this->model->withoutGlobalScopes()
                ->where('id', $data['parent_id'])
                ->where('company_id', $data['company_id'])
                ->first();

            if (!$parent) {
                throw new \Exception(
                    "Parent project type with ID {$data['parent_id']} not found for company {$data['company_id']}. " .
                    "Please ensure the parent project type exists and belongs to the same company."
                );
            }
        }

        $projectType = $this->create($data);

        if (!empty($schemaIds)) {
            $projectType->schemas()->attach($schemaIds);
        }

        return $projectType->fresh(['schemas', 'parent', 'referenceProjectType']);
    }

    public function getSchemasForProjectType(int $projectTypeId)
    {
        $projectType = $this->findOneBy(["id" => $projectTypeId]);

        // If it's a second level project type with schemas, return its schemas
        if ($projectType->is_have_schema && $projectType->schemas()->count() > 0) {
            return $projectType->schemas;
        }

        // If it has a reference project type, get schemas from reference
        if ($projectType->reference_project_type_id) {
            $referenceType = $this->findOneBy(["id" => $projectType->reference_project_type_id]);
            if ($referenceType && $referenceType->schemas()->count() > 0) {
                return $referenceType->schemas;
            }
        }

        // Traverse up the tree to find the second-level parent (child of root)
        $currentNode = $projectType;
        $previousNode = null;

        while ($currentNode && $currentNode->parent_id) {
            $previousNode = $currentNode;
            $currentNode = $this->findOneBy(["id" => $currentNode->parent_id]);

            // If current node has no parent, it's the root
            if ($currentNode && is_null($currentNode->parent_id)) {
                // Previous node is the second-level parent (child of root)
                if ($previousNode && $previousNode->is_have_schema) {
                    return $previousNode->schemas;
                }
                break;
            }
        }

        return collect();
    }

    public function getProjectTypesByFilter(array $filters = []): Collection
    {
        $query = $this->model->query();

        if (isset($filters['second_level']) && $filters['second_level']) {
            $query->secondLevel();
        }

        if (isset($filters['parent_id'])) {
            $query->where('parent_id', $filters['parent_id']);
        }

        if (isset($filters['is_have_schema'])) {
            $query->where('is_have_schema', $filters['is_have_schema']);
        }

        if (isset($filters['is_created'])) {
            $query->where('is_created', $filters['is_created']);
        }

        return $query->where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}
