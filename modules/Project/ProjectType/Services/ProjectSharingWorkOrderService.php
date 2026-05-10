<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Project\ProjectType\Models\ProjectSharingWorkOrder;
use Modules\Project\ProjectType\Repositories\ProjectSharingWorkOrderRepository;

class ProjectSharingWorkOrderService
{
    public function __construct(
        private readonly ProjectSharingWorkOrderRepository $repository
    ) {
    }

    public function list(int $projectTypeId): Collection
    {
        return $this->repository->listByProjectTypeId($projectTypeId);
    }

    public function get(int $id): ProjectSharingWorkOrder
    {
        return $this->repository->findOneOrFail($id);
    }

    public function create(array $data): ProjectSharingWorkOrder
    {
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): ProjectSharingWorkOrder
    {
        $this->repository->update($id, $data);
        return $this->repository->findOneOrFail($id);
    }

    public function delete(int $id): void
    {
        $this->repository->delete($id);
    }
}
