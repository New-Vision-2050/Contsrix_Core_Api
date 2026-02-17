<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
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

    public function getProjectType(UuidInterface $id): ProjectType
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createProjectType(array $data): ProjectType
    {
        return $this->create($data);
    }

    public function updateProjectType(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteProjectType(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
