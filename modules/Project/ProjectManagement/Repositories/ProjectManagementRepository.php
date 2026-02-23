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
            'subSubProjectType',
            'client',
            'costCenterBranch',
            'management',
            'currency',
            'company'
        ])->findOrFail($id->toString());
    }

    public function createProjectManagement(array $data): ProjectManagement
    {
        return $this->create($data);
    }

    public function updateProjectManagement(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteProjectManagement(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
