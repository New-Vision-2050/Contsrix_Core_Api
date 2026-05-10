<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Project\ProjectType\Models\ProjectSharingProcedure;
use Modules\Project\ProjectType\Repositories\ProjectSharingProcedureRepository;

class ProjectSharingProcedureService
{
    public function __construct(
        private readonly ProjectSharingProcedureRepository $repository
    ) {
    }

    public function list(int $projectTypeId): Collection
    {
        return $this->repository->listByProjectTypeId($projectTypeId);
    }

    public function get(int $id): ProjectSharingProcedure
    {
        return $this->repository->findOneOrFail($id);
    }

    public function create(array $data): ProjectSharingProcedure
    {
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): ProjectSharingProcedure
    {
        $this->repository->update($id, $data);
        return $this->repository->findOneOrFail($id);
    }

    public function delete(int $id): void
    {
        $this->repository->delete($id);
    }
}
