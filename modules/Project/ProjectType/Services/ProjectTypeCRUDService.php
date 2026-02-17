<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Project\ProjectType\DTO\CreateProjectTypeDTO;
use Modules\Project\ProjectType\DTO\CreateSecondLevelProjectTypeDTO;
use Modules\Project\ProjectType\Models\ProjectType;
use Modules\Project\ProjectType\Repositories\ProjectTypeRepository;
use App\Traits\HasExportService;

class ProjectTypeCRUDService
{
    use HasExportService;

    public function __construct(
        private ProjectTypeRepository $repository,
    ) {
    }

    public function create(CreateProjectTypeDTO $createProjectTypeDTO): ProjectType
    {
         return $this->repository->createProjectType($createProjectTypeDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(int $id): ProjectType
    {
        return $this->repository->getProjectType(
            id: $id,
        );
    }

    public function getDirectChildren(int $parentId): Collection
    {
        return $this->repository->getDirectChildren($parentId);
    }

    public function getRootProjectTypes(): Collection
    {
        return $this->repository->getRootProjectTypes();
    }

    public function getProjectTypeWithChildren(int $id): ProjectType
    {
        return $this->repository->getProjectTypeWithChildren($id);
    }

    public function getProjectTypeWithSchemas(int $id): ProjectType
    {
        return $this->repository->getProjectTypeWithSchemas($id);
    }

    public function getSecondLevelProjectTypes(): Collection
    {
        return $this->repository->getSecondLevelProjectTypes();
    }

    public function createSecondLevelProjectType(CreateSecondLevelProjectTypeDTO $dto): ProjectType
    {
        return $this->repository->createSecondLevelProjectType(
            $dto->toArray(),
            $dto->getSchemaIds()
        );
    }

    public function getSchemasForProjectType(int $projectTypeId)
    {
        return $this->repository->getSchemasForProjectType($projectTypeId);
    }

    public function getProjectTypesByFilter(array $filters = []): Collection
    {
        return $this->repository->getProjectTypesByFilter($filters);
    }
}
