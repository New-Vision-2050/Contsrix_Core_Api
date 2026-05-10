<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Project\ProjectType\Models\ProjectSharingTasksSetting;
use Modules\Project\ProjectType\Repositories\ProjectSharingTasksSettingRepository;

class ProjectSharingTasksSettingService
{
    public function __construct(
        private readonly ProjectSharingTasksSettingRepository $repository
    ) {
    }

    public function list(int $projectTypeId): Collection
    {
        return $this->repository->listByProjectTypeId($projectTypeId);
    }

    public function get(int $id): ProjectSharingTasksSetting
    {
        return $this->repository->findByIdWithRelations($id);
    }

    public function create(array $data): ProjectSharingTasksSetting
    {
        $record = $this->repository->create($data);
        return $this->repository->findByIdWithRelations($record->id);
    }

    public function update(int $id, array $data): ProjectSharingTasksSetting
    {
        $this->repository->update($id, $data);
        return $this->repository->findByIdWithRelations($id);
    }

    public function delete(int $id): void
    {
        $this->repository->delete($id);
    }
}
