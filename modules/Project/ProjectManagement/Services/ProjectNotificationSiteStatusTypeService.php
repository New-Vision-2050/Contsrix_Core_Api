<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Project\ProjectManagement\DTO\CreateProjectNotificationSiteStatusTypeDTO;
use Modules\Project\ProjectManagement\DTO\UpdateProjectNotificationSiteStatusTypeDTO;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectManagement\Models\ProjectNotificationSiteStatusType;
use Modules\Project\ProjectManagement\Repositories\ProjectNotificationSiteStatusTypeRepository;

class ProjectNotificationSiteStatusTypeService
{
    public function __construct(
        private readonly ProjectNotificationSiteStatusTypeRepository $repository,
    ) {}

    public function list(?int $projectTypeId = null): Collection
    {
        return $this->repository->listActive($projectTypeId);
    }

    public function listWithKeys(?int $projectTypeId = null): Collection
    {
        return $this->repository->listWithActiveKeys($projectTypeId);
    }

    /**
     * Resolve the project_type_id to filter by. Accepts an explicit
     * project_type_id, or a project_id whose project_type_id is looked up.
     * project_type_id takes precedence if both are provided.
     */
    public function resolveProjectTypeId(?int $projectTypeId, ?string $projectId): ?int
    {
        if ($projectTypeId) {
            return $projectTypeId;
        }

        if ($projectId) {
            $project = ProjectManagement::withoutGlobalScopes()->find($projectId);

            return $project?->project_type_id;
        }

        return null;
    }

    public function show(string $id): ProjectNotificationSiteStatusType
    {
        return $this->repository->findOneOrFail($id);
    }

    public function create(CreateProjectNotificationSiteStatusTypeDTO $dto): ProjectNotificationSiteStatusType
    {
        return $this->repository->create($dto->toArray());
    }

    public function update(string $id, UpdateProjectNotificationSiteStatusTypeDTO $dto): ProjectNotificationSiteStatusType
    {
        $type = $this->repository->findOneOrFail($id);
        $type->update($dto->toArray());

        return $type->fresh();
    }

    public function delete(string $id): bool
    {
        $type = $this->repository->findOneOrFail($id);

        return $type->delete();
    }
}
