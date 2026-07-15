<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Project\ProjectManagement\DTO\CreateProjectNotificationSiteStatusTypeDTO;
use Modules\Project\ProjectManagement\DTO\UpdateProjectNotificationSiteStatusTypeDTO;
use Modules\Project\ProjectManagement\Models\ProjectNotificationSiteStatusType;
use Modules\Project\ProjectManagement\Repositories\ProjectNotificationSiteStatusTypeRepository;

class ProjectNotificationSiteStatusTypeService
{
    public function __construct(
        private readonly ProjectNotificationSiteStatusTypeRepository $repository,
    ) {}

    public function list(): Collection
    {
        return $this->repository->listActive();
    }

    public function listWithKeys(): Collection
    {
        return $this->repository->listWithActiveKeys();
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
