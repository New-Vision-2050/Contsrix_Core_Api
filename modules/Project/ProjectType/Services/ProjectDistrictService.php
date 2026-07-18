<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Project\ProjectType\Models\ProjectDistrict;
use Modules\Project\ProjectType\Repositories\ProjectDistrictRepository;

class ProjectDistrictService
{
    public function __construct(
        private readonly ProjectDistrictRepository $repository
    ) {
    }

    public function list(): Collection
    {
        return $this->repository->list();
    }

    public function get(int $id): ProjectDistrict
    {
        return $this->repository->findOneOrFail($id);
    }

    public function create(array $data): ProjectDistrict
    {
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): ProjectDistrict
    {
        $this->repository->update($id, $data);
        return $this->repository->findOneOrFail($id);
    }

    public function delete(int $id): void
    {
        $this->repository->delete($id);
    }
}
