<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Project\ProjectType\Models\OrderPermitTasksSetting;
use Modules\Project\ProjectType\Repositories\OrderPermitTasksSettingRepository;

class OrderPermitTasksSettingService
{
    public function __construct(
        private readonly OrderPermitTasksSettingRepository $repository
    ) {
    }

    public function list(int $projectTypeId): Collection
    {
        return $this->repository->listByProjectTypeId($projectTypeId);
    }

    public function get(int $id): OrderPermitTasksSetting
    {
        return $this->repository->findByIdWithRelations($id);
    }

    public function create(array $data): OrderPermitTasksSetting
    {
        $record = $this->repository->create($data);
        return $this->repository->findByIdWithRelations($record->id);
    }

    public function update(int $id, array $data): OrderPermitTasksSetting
    {
        $this->repository->update($id, $data);
        return $this->repository->findByIdWithRelations($id);
    }

    public function delete(int $id): void
    {
        $this->repository->delete($id);
    }
}
