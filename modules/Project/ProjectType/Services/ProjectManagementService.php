<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Project\ProjectType\Models\ProjectManagement;
use Modules\Project\ProjectType\Repositories\ProjectManagementRepository;

class ProjectManagementService
{
    public function __construct(
        private readonly ProjectManagementRepository $repository
    ) {
    }

    public function list(): Collection
    {
        return $this->repository->list();
    }

    public function get(int $id): ProjectManagement
    {
        return $this->repository->findOneOrFail($id);
    }

    public function create(array $data): ProjectManagement
    {
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): ProjectManagement
    {
        $this->repository->update($id, $data);
        return $this->repository->findOneOrFail($id);
    }

    public function delete(int $id): void
    {
        $this->repository->delete($id);
    }
}
