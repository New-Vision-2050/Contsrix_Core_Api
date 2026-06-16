<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Services;

use Illuminate\Support\Collection;
use Modules\ProcedureSetting\DTO\CreateProcedureSettingDTO;
use Modules\ProcedureSetting\Models\ProcedureSetting;
use Modules\ProcedureSetting\Models\WorkFlow;
use Modules\ProcedureSetting\Repositories\ProcedureSettingRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class ProcedureSettingCRUDService
{
    use HasExportService;

    public function __construct(
        private ProcedureSettingRepository $repository,
    ) {
    }

    public function create(CreateProcedureSettingDTO $createProcedureSettingDTO): ProcedureSetting
    {
         return $this->repository->createProcedureSetting($createProcedureSettingDTO->toArray());
    }

    public function list(): Collection
    {
        return $this->repository->list();
    }

    public function listByWorkFlow(array $filters = []): Collection
    {
        return $this->repository->listByWorkFlow($filters);
    }

    public function firstByWorkFlowFilters(array $filters = []): ?WorkFlow
    {
        return $this->repository->firstByWorkFlowFilters($filters);
    }

    public function getDefaultWorkFlowForList(): ?WorkFlow
    {
        return $this->repository->getDefaultWorkFlowForList();
    }

    public function getDefaultWorkFlowByType(string $type, ?string $parentId = null): ?WorkFlow
    {
        return $this->repository->getDefaultWorkFlowByType($type, $parentId);
    }

    public function toggleBranchDefaultWorkFlows(int $branchId, bool $checked, string $type): ?WorkFlow
    {
        return $this->repository->toggleBranchDefaultWorkFlows($branchId, $checked, $type);
    }

    public function listByParentId(string $parentId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repository->list(['parent_id' => $parentId]);
    }

    public function get(UuidInterface $id): ProcedureSetting
    {
        return $this->repository->getProcedureSetting(
            id: $id,
        );
    }
}
