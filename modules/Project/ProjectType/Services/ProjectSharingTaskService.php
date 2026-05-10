<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Project\ProjectType\Models\ProjectSharingTask;
use Modules\Project\ProjectType\Repositories\ProjectSharingTaskRepository;

class ProjectSharingTaskService
{
    public function __construct(
        private readonly ProjectSharingTaskRepository $repository
    ) {
    }

    public function list(int $projectTypeId): Collection
    {
        return $this->repository->listByProjectTypeId($projectTypeId);
    }

    public function get(int $id): ProjectSharingTask
    {
        return $this->repository->findOneOrFail($id);
    }

    public function create(array $data): ProjectSharingTask
    {
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): ProjectSharingTask
    {
        $this->repository->update($id, $data);
        return $this->repository->findOneOrFail($id);
    }

    public function delete(int $id): void
    {
        $this->repository->delete($id);
    }
}
