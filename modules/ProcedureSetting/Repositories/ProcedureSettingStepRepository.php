<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\ProcedureSetting\Models\ProcedureSettingStep;

/**
 * @property ProcedureSettingStep $model
 * @method ProcedureSettingStep findOneOrFail($id)
 */
class ProcedureSettingStepRepository extends BaseRepository
{
    public function __construct(ProcedureSettingStep $model)
    {
        parent::__construct($model);
    }

    public function getStepsByProcedureSettingId(string $procedureSettingId): Collection
    {
        return $this->model
            ->with('employee')
            ->where('procedure_setting_id', $procedureSettingId)
            ->orderBy('id')
            ->get();
    }

    public function getProcedureSettingStep(int $id): ProcedureSettingStep
    {
        return $this->model->with('employee')->findOrFail($id);
    }

    public function createProcedureSettingStep(array $data): ProcedureSettingStep
    {
        return $this->create($data);
    }

    public function updateProcedureSettingStep(int $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteProcedureSettingStep(int $id): bool
    {
        return $this->delete($id);
    }
}
