<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\ProcedureSetting\Models\ProcedureSettingStep;
use Modules\ProcedureSetting\Models\ProcedureSettingStepActionTaker;
use Modules\ProcedureSetting\Models\ProcedureSettingStepConcernedManagementHierarchy;

/**
 * @property ProcedureSettingStep $model
 * @method ProcedureSettingStep findOneOrFail($id)
 */
class ProcedureSettingStepRepository extends BaseRepository
{
    private const STEP_WITH = [
        'branch',
        'management',
        'escalationManagementHierarchy',
        'actionTakers.user',
        'concernedManagementHierarchies.managementHierarchy',
    ];

    public function __construct(ProcedureSettingStep $model)
    {
        parent::__construct($model);
    }

    public function getStepsByProcedureSettingId(string $procedureSettingId): Collection
    {
        return $this->model
            ->with(self::STEP_WITH)
            ->where('procedure_setting_id', $procedureSettingId)
            ->orderByRaw('(step_order IS NULL) ASC')
            ->orderBy('step_order')
            ->orderBy('id')
            ->get();
    }

    public function getProcedureSettingStep(int $id): ProcedureSettingStep
    {
        return $this->model->with(self::STEP_WITH)->findOrFail($id);
    }

    public function createProcedureSettingStep(array $data): ProcedureSettingStep
    {
        [$syncAction, $syncConcerned, $actionIds, $concernedIds, $payload] = $this->splitUserSyncPayload($data);

        $model = $this->create($payload);
        $model->refresh();

        if ($syncAction) {
            $this->replaceActionTakers($model, (array) $actionIds);
        }
        if ($syncConcerned) {
            $this->replaceConcernedManagementHierarchies($model, (array) $concernedIds);
        }

        return $model->load(self::STEP_WITH);
    }

    public function updateProcedureSettingStep(int $id, array $data): bool
    {
        [$syncAction, $syncConcerned, $actionIds, $concernedIds, $payload] = $this->splitUserSyncPayload($data);

        if ($payload !== [] && ! $this->update($id, $payload)) {
            return false;
        }

        $model = $this->model->newQuery()->findOrFail($id);

        if ($syncAction) {
            $this->replaceActionTakers($model, (array) $actionIds);
        }
        if ($syncConcerned) {
            $this->replaceConcernedManagementHierarchies($model, (array) $concernedIds);
        }

        return true;
    }

    public function deleteProcedureSettingStep(int $id): bool
    {
        return $this->delete($id);
    }

    /**
     * @param array<string, mixed> $data
     * @return array{0: bool, 1: bool, 2: mixed, 3: mixed, 4: array<string, mixed>}
     */
    private function splitUserSyncPayload(array $data): array
    {
        $syncAction = array_key_exists('action_taker_user_ids', $data);
        $syncConcerned = array_key_exists('concerned_management_hierarchy_ids', $data);
        $actionIds = $data['action_taker_user_ids'] ?? null;
        $concernedIds = $data['concerned_management_hierarchy_ids'] ?? null;

        unset($data['action_taker_user_ids'], $data['concerned_management_hierarchy_ids']);

        return [$syncAction, $syncConcerned, $actionIds, $concernedIds, $data];
    }

    private function replaceActionTakers(ProcedureSettingStep $step, array $userIds): void
    {
        ProcedureSettingStepActionTaker::query()
            ->where('procedure_setting_step_id', $step->id)
            ->delete();

        foreach (array_unique(array_values(array_filter($userIds, static fn ($id) => is_string($id) && $id !== ''))) as $userId) {
            ProcedureSettingStepActionTaker::query()->create([
                'procedure_setting_step_id' => $step->id,
                'user_id'                   => $userId,
                'company_id'                => $step->company_id,
            ]);
        }
    }

    private function replaceConcernedManagementHierarchies(ProcedureSettingStep $step, array $managementHierarchyIds): void
    {
        ProcedureSettingStepConcernedManagementHierarchy::query()
            ->where('procedure_setting_step_id', $step->id)
            ->delete();

        foreach (array_unique(array_values(array_filter($managementHierarchyIds, static fn ($id) => is_int($id) && $id > 0))) as $mhId) {
            ProcedureSettingStepConcernedManagementHierarchy::query()->create([
                'procedure_setting_step_id' => $step->id,
                'management_hierarchy_id'   => $mhId,
                'company_id'                => $step->company_id,
            ]);
        }
    }
}
