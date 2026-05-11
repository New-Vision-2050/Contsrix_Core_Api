<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Project\ProjectType\Models\OrderPermit;
use Modules\Project\ProjectType\Repositories\OrderPermitRepository;

class OrderPermitService
{
    public function __construct(
        private readonly OrderPermitRepository $repository
    ) {
    }

    public function list(int $projectTypeId): Collection
    {
        return $this->repository->listByProjectTypeId($projectTypeId);
    }

    public function get(int $id): OrderPermit
    {
        return $this->repository->findOneOrFail($id);
    }

    public function create(array $data): OrderPermit
    {
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): OrderPermit
    {
        $this->repository->update($id, $data);
        return $this->repository->findOneOrFail($id);
    }

    public function delete(int $id): void
    {
        $this->repository->delete($id);
    }
}
