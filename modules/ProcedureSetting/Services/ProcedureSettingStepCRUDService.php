<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\ProcedureSetting\DTO\CreateProcedureSettingStepDTO;
use Modules\ProcedureSetting\Models\ProcedureSettingStep;
use Modules\ProcedureSetting\Repositories\ProcedureSettingStepRepository;

class ProcedureSettingStepCRUDService
{
    public function __construct(
        private ProcedureSettingStepRepository $repository,
    ) {
    }

    public function create(CreateProcedureSettingStepDTO $dto): ProcedureSettingStep
    {
        return $this->repository->createProcedureSettingStep($dto->toArray());
    }

    public function get(int $id): ProcedureSettingStep
    {
        return $this->repository->getProcedureSettingStep($id);
    }

    public function getByProcedureSettingId(string $procedureSettingId): Collection
    {
        return $this->repository->getStepsByProcedureSettingId($procedureSettingId);
    }
}
