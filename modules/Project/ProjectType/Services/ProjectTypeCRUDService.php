<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Services;

use Illuminate\Support\Collection;
use Modules\Project\ProjectType\DTO\CreateProjectTypeDTO;
use Modules\Project\ProjectType\Models\ProjectType;
use Modules\Project\ProjectType\Repositories\ProjectTypeRepository;
use Ramsey\Uuid\UuidInterface;
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

    public function get(UuidInterface $id): ProjectType
    {
        return $this->repository->getProjectType(
            id: $id,
        );
    }
}
