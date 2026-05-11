<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Project\ProjectType\Models\OrderPermitDepartment;
use Modules\Project\ProjectType\Repositories\OrderPermitDepartmentRepository;

class OrderPermitDepartmentService
{
    public function __construct(
        private readonly OrderPermitDepartmentRepository $repository
    ) {
    }

    public function list(int $projectTypeId): Collection
    {
        return $this->repository->listByProjectTypeId($projectTypeId);
    }

    public function get(int $id): OrderPermitDepartment
    {
        return $this->repository->findOneOrFail($id);
    }

    public function create(array $data): OrderPermitDepartment
    {
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): OrderPermitDepartment
    {
        $this->repository->update($id, $data);
        return $this->repository->findOneOrFail($id);
    }

    public function delete(int $id): void
    {
        $this->repository->delete($id);
    }
}
