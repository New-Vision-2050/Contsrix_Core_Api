<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Services;

use Illuminate\Support\Collection;
use Modules\Project\ProjectManagement\DTO\CreateProjectManagementDTO;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectManagement\Repositories\ProjectManagementRepository;
use Modules\User\Models\User;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class ProjectManagementCRUDService
{
    use HasExportService;

    public function __construct(
        private ProjectManagementRepository $repository,
    ) {
    }

    public function create(CreateProjectManagementDTO $createProjectManagementDTO): ProjectManagement
    {
         return $this->repository->createProjectManagement($createProjectManagementDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10, ?User $user = null): array
    {
        return $this->repository->paginatedForUser(
            page: $page,
            perPage: $perPage,
            user: $user,
        );
    }

    public function get(UuidInterface $id): ProjectManagement
    {
        return $this->repository->getProjectManagement(
            id: $id,
        );
    }
}
