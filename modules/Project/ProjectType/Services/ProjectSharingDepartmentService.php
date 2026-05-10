<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Project\ProjectType\Models\ProjectSharingDepartment;
use Modules\Project\ProjectType\Repositories\ProjectSharingDepartmentRepository;

class ProjectSharingDepartmentService
{
    public function __construct(
        private readonly ProjectSharingDepartmentRepository $repository
    ) {
    }

    public function list(int $projectTypeId): Collection
    {
        return $this->repository->listByProjectTypeId($projectTypeId);
    }

    public function get(int $id): ProjectSharingDepartment
    {
        return $this->repository->findOneOrFail($id);
    }

    public function create(array $data): ProjectSharingDepartment
    {
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): ProjectSharingDepartment
    {
        $this->repository->update($id, $data);
        return $this->repository->findOneOrFail($id);
    }

    public function delete(int $id): void
    {
        $this->repository->delete($id);
    }
}
