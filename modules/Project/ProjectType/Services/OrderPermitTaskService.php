<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Project\ProjectType\Models\OrderPermitTask;
use Modules\Project\ProjectType\Repositories\OrderPermitTaskRepository;

class OrderPermitTaskService
{
    public function __construct(
        private readonly OrderPermitTaskRepository $repository
    ) {
    }

    public function list(int $projectTypeId): Collection
    {
        return $this->repository->listByProjectTypeId($projectTypeId);
    }

    public function get(int $id): OrderPermitTask
    {
        return $this->repository->findOneOrFail($id);
    }

    public function create(array $data): OrderPermitTask
    {
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): OrderPermitTask
    {
        $this->repository->update($id, $data);
        return $this->repository->findOneOrFail($id);
    }

    public function delete(int $id): void
    {
        $this->repository->delete($id);
    }
}
