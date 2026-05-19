<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\DTO;

use Modules\ProcedureSetting\Models\ProcedureSettingStep;

/**
 * Immutable result returned by ProcedureWorkflowService::advance().
 *
 * - $isFinal === true  → caller should apply its terminal action (status = approved, etc.)
 *                        and clear current_procedure_step_id.
 * - $isFinal === false → caller should set current_procedure_step_id = $nextStep->id
 *                        and keep the entity in its pending state.
 */
final readonly class ProcedureWorkflowResult
{
    public function __construct(
        public ProcedureSettingStep $currentStep,
        public ?ProcedureSettingStep $nextStep,
        public bool $isFinal,
    ) {}
}
