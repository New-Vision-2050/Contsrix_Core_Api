<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Repositories;

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
        return $this->findOneByOrFail([
            'id' => $id,
        ]);
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
        $projectType = $this->create($data);

        if (!empty($schemaIds)) {
            $projectType->schemas()->attach($schemaIds);
        }

        return $projectType->fresh(['schemas', 'parent', 'referenceProjectType']);
    }

    public function getSchemasForProjectType(int $projectTypeId): Collection
    {
        $projectType = $this->findById($projectTypeId);

        // If it's a second level project type with schemas, return its schemas
        if ($projectType->is_have_schema && $projectType->schemas()->count() > 0) {
            return $projectType->schemas;
        }

        // If it has a reference project type, get schemas from reference
        if ($projectType->reference_project_type_id) {
            $referenceType = $this->findById($projectType->reference_project_type_id);
            if ($referenceType && $referenceType->schemas()->count() > 0) {
                return $referenceType->schemas;
            }
        }

        // If it's a child of second level, get parent's schemas
        if ($projectType->parent_id) {
            $parent = $this->findById($projectType->parent_id);

            // Check if parent is second level (has parent that is root)
            if ($parent && $parent->parent_id) {
                $grandParent = $this->findById($parent->parent_id);
                if ($grandParent && is_null($grandParent->parent_id)) {
                    // Parent is second level, return its schemas
                    return $parent->schemas;
                }
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
