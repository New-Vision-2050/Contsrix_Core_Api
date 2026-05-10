<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Project\ProjectType\Models\ReportForm;
use Modules\Project\ProjectType\Repositories\ReportFormRepository;

class ReportFormService
{
    public function __construct(
        private readonly ReportFormRepository $repository
    ) {
    }

    public function list(int $projectTypeId): Collection
    {
        return $this->repository->listByProjectTypeId($projectTypeId);
    }

    public function get(int $id): ReportForm
    {
        return $this->repository->findOneOrFail($id);
    }

    public function create(array $data): ReportForm
    {
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): ReportForm
    {
        $this->repository->update($id, $data);
        return $this->repository->findOneOrFail($id);
    }

    public function delete(int $id): void
    {
        $this->repository->delete($id);
    }
}
