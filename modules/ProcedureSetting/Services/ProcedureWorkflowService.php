<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Services;

use Modules\ProcedureSetting\DTO\ProcedureWorkflowResult;
use Modules\ProcedureSetting\Exceptions\ProcedureWorkflowException;
use Modules\ProcedureSetting\Models\ProcedureSetting;
use Modules\ProcedureSetting\Models\ProcedureSettingStep;

/**
 * Single source of truth for stepping through a ProcedureSetting workflow.
 *
 * Consumed by any entity service that uses procedure_settings (employee tasks,
 * price offers, contracts, client requests, …). The brain lives here so that a
 * change to step rules — authorization, ordering, escalation — propagates to
 * every consumer.
 *
 * Convention used by callers:
 *  - Persist `procedure_setting_id` and `current_procedure_step_id` on the
 *    entity row.
 *  - Call resolveFirstStep() at creation time.
 *  - Call advance() when an action-taker approves.
 *  - Call assertCanReject() when an action-taker rejects.
 */
final class ProcedureWorkflowService
{
    /**
     * Resolve the first step for a procedure setting of a given type.
     *
     * Used by entity services at creation time to seed
     * `current_procedure_step_id` on the new row.
     */
    public function resolveFirstStep(string $procedureType): ProcedureSettingStep
    {
        /** @var ProcedureSetting|null $setting */
        $setting = ProcedureSetting::query()
            ->where('type', $procedureType)
            ->with(['steps' => fn ($q) => $q->orderBy('step_order')])
            ->first();

        if (!$setting) {
            throw ProcedureWorkflowException::noStepsConfigured();
        }

        $firstStep = $setting->steps->first();

        if (!$firstStep) {
            throw ProcedureWorkflowException::noStepsConfigured();
        }

        return $firstStep;
    }

    /**
     * Resolve the first step for a procedure setting identified by its UUID.
     *
     * Used when an entity inherits a procedure from a parent (e.g. extension
     * requests inheriting the parent task's procedure_setting_id) rather than
     * resolving from a type string.
     */
    public function resolveFirstStepBySettingId(string $procedureSettingId): ProcedureSettingStep
    {
        $firstStep = ProcedureSettingStep::query()
            ->where('procedure_setting_id', $procedureSettingId)
            ->orderBy('step_order')
            ->first();

        if (!$firstStep) {
            throw ProcedureWorkflowException::noStepsConfigured();
        }

        return $firstStep;
    }

    /**
     * Advance the workflow after an authorized action-taker has approved the
     * current step. Caller decides whether to update the entity to the next
     * step or apply its terminal action based on $result->isFinal.
     *
     * @param int|null    $currentStepId       The step the entity currently awaits.
     * @param string|null $procedureSettingId  The parent procedure id.
     * @param string      $userId              The user attempting to act.
     */
    public function advance(
        ?int $currentStepId,
        ?string $procedureSettingId,
        string $userId,
        ?string $createdByUserId = null,
    ): ProcedureWorkflowResult {
        $currentStep = $this->loadStep($currentStepId);

        $this->assertIsActionTaker($currentStep, $userId, $createdByUserId);

        $nextStep = null;
        if ($procedureSettingId && $currentStep->step_order !== null) {
            $nextStep = ProcedureSettingStep::query()
                ->where('procedure_setting_id', $procedureSettingId)
                ->where('step_order', '>', $currentStep->step_order)
                ->orderBy('step_order')
                ->first();
        }

        return new ProcedureWorkflowResult(
            currentStep: $currentStep,
            nextStep:    $nextStep,
            isFinal:     $nextStep === null,
        );
    }

    /**
     * Verify a user is allowed to reject the entity's current step.
     * Rejection always terminates the workflow — there is no "next step" to
     * compute, so this method returns void and lets the caller apply the
     * entity's terminal rejection state.
     */
    public function assertCanReject(?int $currentStepId, string $userId, ?string $createdByUserId = null): void
    {
        $step = $this->loadStep($currentStepId);
        $this->assertIsActionTaker($step, $userId, $createdByUserId);
    }

    /**
     * Preview the approval responsible(s) for a procedure type — the user(s)
     * who would need to act on the FIRST step if an entity of this type were
     * created right now.
     *
     * Designed for creation-form UIs that need to show "مسؤل الاعتماد" before
     * the entity is persisted.
     *
     * Returns:
     *  - auto_approve = true when no procedure setting exists, no steps are
     *    configured, OR the first step has no action-takers → the caller
     *    should create the entity in `approved` state directly.
     *  - auto_approve = false when the first step has explicit action-takers
     *    → those users must approve.
     *
     * @return array{
     *   auto_approve: bool,
     *   step: array{id:int,name:?string,step_order:int}|null,
     *   action_takers: list<array{user_id:string,name:?string}>
     * }
     */
    public function getApprovalResponsibles(string $procedureType, ?string $createdByUserId = null): array
    {
        /** @var ProcedureSetting|null $setting */
        $setting = ProcedureSetting::query()
            ->where('type', $procedureType)->orderBy("sort_order")
            ->with(['steps' => fn ($q) => $q->orderBy('step_order')->with('actionTakers.user')])
            ->first();

        if (!$setting) {
            return ['auto_approve' => true, 'step' => null, 'action_takers' => []];
        }

        $firstStep = $setting->steps->first();

        if (!$firstStep) {
            return ['auto_approve' => true, 'step' => null, 'action_takers' => []];
        }

        $actionTakerType = $firstStep->action_taker_type?->value ?? 'specific_user';

        if ($actionTakerType === 'management_hierarchy' && $createdByUserId !== null) {
            $resolvedUserId = $this->resolveManagerFromCreatorHierarchy($firstStep, $createdByUserId);

            if ($resolvedUserId !== null) {
                $user = \Modules\User\Models\User::query()->find($resolvedUserId);

                return [
                    'auto_approve'  => false,
                    'step'          => [
                        'id'         => $firstStep->id,
                        'name'       => $firstStep->name,
                        'step_order' => $firstStep->step_order,
                    ],
                    'action_takers' => [
                        [
                            'user_id' => $resolvedUserId,
                            'name'    => $user?->name,
                        ],
                    ],
                ];
            }
        }

        $actionTakers = [];
        foreach ($firstStep->actionTakers as $at) {
            $actionTakers[] = [
                'user_id' => $at->user_id,
                'name'    => $at->relationLoaded('user') && $at->user ? $at->user->name : null,
            ];
        }

        return [
            'auto_approve'  => $actionTakers === [],
            'step'          => [
                'id'         => $firstStep->id,
                'name'       => $firstStep->name,
                'step_order' => $firstStep->step_order,
            ],
            'action_takers' => $actionTakers,
        ];
    }

    /**
     * Quick check used by inbox-style endpoints: would this user be allowed to
     * act on the given step? Returns true for open steps (no action-takers)
     * and true when user is explicitly listed.
     */
    public function userCanActOnStep(ProcedureSettingStep $step, string $userId, ?string $createdByUserId = null): bool
    {
        if (!$step->relationLoaded('actionTakers')) {
            $step->load('actionTakers');
        }

        $actionTakerType = $step->action_taker_type?->value ?? 'specific_user';

        if ($actionTakerType === 'management_hierarchy' && $createdByUserId !== null) {
            $resolvedUserId = $this->resolveManagerFromCreatorHierarchy($step, $createdByUserId);

            return $resolvedUserId !== null && $resolvedUserId === $userId;
        }

        if ($step->actionTakers->isEmpty()) {
            return true;
        }

        return $step->actionTakers->contains('user_id', $userId);
    }

    private function loadStep(?int $stepId): ProcedureSettingStep
    {
        if (!$stepId) {
            throw ProcedureWorkflowException::noActiveStep();
        }

        $step = ProcedureSettingStep::with('actionTakers')->find($stepId);

        if (!$step) {
            throw ProcedureWorkflowException::stepNotFound();
        }

        return $step;
    }

    private function assertIsActionTaker(ProcedureSettingStep $step, string $userId, ?string $createdByUserId = null): void
    {
        $actionTakerType = $step->action_taker_type?->value ?? 'specific_user';

        if ($actionTakerType === 'management_hierarchy' && $createdByUserId !== null) {
            $resolvedUserId = $this->resolveManagerFromCreatorHierarchy($step, $createdByUserId);

            if ($resolvedUserId !== null && $resolvedUserId === $userId) {
                return;
            }

            throw ProcedureWorkflowException::notAuthorized();
        }

        if ($step->actionTakers->isEmpty()) {
            return;
        }

        if (!$step->actionTakers->contains('user_id', $userId)) {
            throw ProcedureWorkflowException::notAuthorized();
        }
    }

    private function resolveManagerFromCreatorHierarchy(ProcedureSettingStep $step, string $createdByUserId): ?string
    {
        $hierarchyType = $step->action_taker_management_hierarchy_type?->value;

        if ($hierarchyType === null) {
            return null;
        }

        $creator = \Modules\User\Models\User::query()
            ->with('professionalData')
            ->find($createdByUserId);

        if ($creator === null) {
            return null;
        }

        $professionalData = $creator->professionalData;

        if ($professionalData === null) {
            return null;
        }

        $hierarchyId = null;
        if ($hierarchyType === 'branch_manager') {
            $hierarchyId = $professionalData->branch_id;
        } elseif ($hierarchyType === 'management_manager') {
            $hierarchyId = $professionalData->management_id;
        }

        if ($hierarchyId === null) {
            return null;
        }

        $hierarchy = \Modules\Company\ManagementHierarchy\Models\ManagementHierarchy::query()
            ->find($hierarchyId);

        if ($hierarchy === null || $hierarchy->manager_id === null) {
            return null;
        }

        return (string) $hierarchy->manager_id;
    }
}
