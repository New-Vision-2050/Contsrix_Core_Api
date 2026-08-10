<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Services;

use Modules\ProcedureSetting\Models\ProcedureSettingStep;
use Modules\ProcedureSetting\Models\ProcedureSettingStepActionTaker;
use Modules\ProcedureSetting\Models\ProcedureSettingStepConcernedManagementHierarchy;

final class ProcedureSettingCloneService
{
    public function duplicateSteps(string $sourceProcedureSettingId, string $targetProcedureSettingId): void
    {
        $sourceSteps = ProcedureSettingStep::query()
            ->withoutGlobalScopes()
            ->with(['actionTakers', 'concernedManagementHierarchies'])
            ->where('procedure_setting_id', $sourceProcedureSettingId)
            ->orderByRaw('(step_order IS NULL) ASC')
            ->orderBy('step_order')
            ->orderBy('id')
            ->get();

        $fillable = (new ProcedureSettingStep)->getFillable();

        foreach ($sourceSteps as $sourceStep) {
            $payload = [];
            foreach ($fillable as $key) {
                $payload[$key] = $key === 'procedure_setting_id'
                    ? $targetProcedureSettingId
                    : $sourceStep->getAttribute($key);
            }

            $targetStep = ProcedureSettingStep::query()
                ->withoutGlobalScopes()
                ->create($payload);

            foreach ($sourceStep->actionTakers as $actionTaker) {
                ProcedureSettingStepActionTaker::query()->create([
                    'procedure_setting_step_id' => $targetStep->id,
                    'user_id' => $actionTaker->user_id,
                    'company_id' => $targetStep->company_id,
                ]);
            }

            foreach ($sourceStep->concernedManagementHierarchies as $concernedManagementHierarchy) {
                ProcedureSettingStepConcernedManagementHierarchy::query()->create([
                    'procedure_setting_step_id' => $targetStep->id,
                    'management_hierarchy_id' => $concernedManagementHierarchy->management_hierarchy_id,
                    'company_id' => $targetStep->company_id,
                ]);
            }
        }
    }
}
