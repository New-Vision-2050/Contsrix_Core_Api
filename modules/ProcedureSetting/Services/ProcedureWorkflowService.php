<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Services;

use Modules\ProcedureSetting\DTO\ProcedureWorkflowResult;
use Modules\ProcedureSetting\Exceptions\ProcedureWorkflowException;
use Modules\ProcedureSetting\Models\InternalProcedureTaken;
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
    public function __construct(
        private readonly ActionTakerResolver $resolver,
        private readonly WorkflowEngine $engine,
    ) {}

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
            ->whereNull('parent_id')
            ->with(['steps' => fn ($q) => $q->orderBy('step_order')])
            ->first();

        if (! $setting) {
            throw ProcedureWorkflowException::noStepsConfigured();
        }

        $firstStep = $setting->steps->first();

        if (! $firstStep) {
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
     *
     * Returns null when no steps are configured so callers can auto-approve
     * instead of throwing a 500 error.
     */
    public function resolveFirstStepBySettingId(string $procedureSettingId): ?ProcedureSettingStep
    {
        $ids = [$procedureSettingId];
        $ids = array_merge($ids, $this->collectDescendantIds($procedureSettingId));

        $firstStep = ProcedureSettingStep::query()
            ->whereIn('procedure_setting_id', $ids)
            ->orderBy('step_order')
            ->first();

        return $firstStep;
    }

    /**
     * Advance the workflow after an authorized action-taker has approved the
     * current step. Caller decides whether to update the entity to the next
     * step or apply its terminal action based on $result->isFinal.
     *
     * When $processableType and $processableId are provided and the workflow
     * is final, the internal procedure is automatically marked as "taken" in
     * the central internal_procedure_takens morph table.
     *
     * @param  int|null  $currentStepId  The step the entity currently awaits.
     * @param  string|null  $procedureSettingId  The parent procedure id.
     * @param  string  $userId  The user attempting to act.
     * @param  string|null  $processableType  Morph type of the entity (e.g. 'employee_task').
     * @param  string|null  $processableId  Morph ID of the entity.
     */
    public function advance(
        ?int $currentStepId,
        ?string $procedureSettingId,
        string $userId,
        ?string $createdByUserId = null,
        array $context = [],
        ?string $processableType = null,
        ?string $processableId = null,
    ): ProcedureWorkflowResult {
        $currentStep = $this->loadStep($currentStepId);

        $this->assertIsActionTaker($currentStep, $userId, $createdByUserId, $context);

        $nextStep = null;
        if ($procedureSettingId && $currentStep->step_order !== null) {
            $ids = [$procedureSettingId];
            $ids = array_merge($ids, $this->collectDescendantIds($procedureSettingId));

            $nextStep = ProcedureSettingStep::query()
                ->whereIn('procedure_setting_id', $ids)
                ->where('step_order', '>', $currentStep->step_order)
                ->orderBy('step_order')
                ->first();
        }

        $isFinal = $nextStep === null;

        // Auto-mark the internal procedure as taken when the workflow is final.
        if ($isFinal && $processableType !== null && $processableId !== null && $procedureSettingId !== null) {
            $this->markProcedureTaken($processableType, $processableId, $procedureSettingId, $userId);
        }

        return new ProcedureWorkflowResult(
            currentStep: $currentStep,
            nextStep: $nextStep,
            isFinal: $isFinal,
        );
    }

    /**
     * Verify a user is allowed to reject the entity's current step.
     * Rejection always terminates the workflow — there is no "next step" to
     * compute, so this method returns void and lets the caller apply the
     * entity's terminal rejection state.
     */
    public function assertCanReject(?int $currentStepId, string $userId, ?string $createdByUserId = null, array $context = []): void
    {
        $step = $this->loadStep($currentStepId);
        $this->assertIsActionTaker($step, $userId, $createdByUserId, $context);
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
     */
    public function getApprovalResponsibles(string $procedureType, ?string $createdByUserId = null, array $context = [], ?string $formKey = null)
    {
        $companyId = (string) tenant('id');
        $branchId = isset($context['branch_id']) ? (string) $context['branch_id'] : null;

        return $this->engine->previewResponsibles(
            $procedureType,
            $formKey,
            $companyId,
            $branchId,
            $createdByUserId,
            $context,
        );
    }

    /**
     * Quick check used by inbox-style endpoints: would this user be allowed to
     * act on the given step? Returns true for open steps (no action-takers)
     * and true when user is explicitly listed.
     */
    public function userCanActOnStep(ProcedureSettingStep $step, string $userId, ?string $createdByUserId = null, array $context = []): bool
    {
        $actionTakerType = $step->action_taker_type?->value ?? 'specific_user';

        // Dynamic types: resolve all authorized users and check membership.
        if (in_array($actionTakerType, ['management_hierarchy', 'specific_procedures', 'himself'], true)) {
            $resolvedUserIds = $this->resolver->resolveUsersForStep($step, $createdByUserId, $context);

            return in_array($userId, $resolvedUserIds, true);
        }

        if (! $step->relationLoaded('actionTakers')) {
            $step->load('actionTakers');
        }

        if ($step->actionTakers->isEmpty()) {
            return true;
        }

        return $step->actionTakers->contains('user_id', $userId);
    }

    /**
     * Resolve the user IDs authorized to act on a given step.
     * Useful for broadcasting notifications to the correct recipients.
     */
    public function resolveActionTakerUserIdsForStep(ProcedureSettingStep $step, ?string $createdByUserId = null, array $context = []): array
    {
        return $this->resolver->resolveUsersForStep($step, $createdByUserId, $context);
    }

    /**
     * Resolve the first procedure setting for a type scoped to company and branch workflow.
     */
    public function resolveProcedureSettingForBranch(
        string $procedureType,
        string $companyId,
        ?string $branchId,
    ): ?ProcedureSetting {
        return $this->engine->resolveParentSetting($procedureType, $companyId, $branchId);
    }

    /**
     * Resolve the InternalProcedureSetting (child row with form set) for a given
     * category type + form key, scoped to company and optionally a branch.
     *
     * Used by entity services (extension, approval) to find which child
     * procedure setting handles a specific action form.
     */
    public function resolveInternalProcedureSettingByForm(
        string $procedureCategoryType,
        string $formKey,
        string $companyId,
        ?string $branchId = null,
    ): ?ProcedureSetting {
        $setting = $this->engine
            ->resolveSettingsForEntry($procedureCategoryType, $formKey, $companyId, $branchId)
            ->first();

        if ($setting !== null) {
            $setting->load(['steps' => fn ($query) => $query->orderBy('step_order')]);

            // No steps configured → treat as auto-approve (return null so callers
            // skip the workflow and handle the action directly).
            if ($setting->steps->isEmpty()) {
                return null;
            }
        }

        return $setting;
    }

    /**
     * Mark an internal procedure as "taken" for a given processable entity.
     *
     * Central entry point for recording that a procedure has been completed
     * or approved. Uses the internal_procedure_takens morph table so any
     * entity type can track its taken procedures.
     */
    public function markProcedureTaken(
        string $processableType,
        string $processableId,
        string $procedureSettingId,
        ?string $takenBy = null,
    ): void {
        $setting = ProcedureSetting::find($procedureSettingId);

        InternalProcedureTaken::query()->firstOrCreate(
            [
                'processable_type'      => $processableType,
                'processable_id'        => $processableId,
                'procedure_setting_id'  => $procedureSettingId,
            ],
            [
                'company_id'   => $setting?->company_id ?? (string) tenant('id'),
                'form'         => $setting?->form,
                'taken_by'     => $takenBy,
                'taken_at'     => now(),
            ],
        );
    }

    /**
     * Get all taken internal procedure setting IDs for a given processable entity.
     *
     * @return list<string>
     */
    public function getTakenProcedureIds(string $processableType, string $processableId): array
    {
        return InternalProcedureTaken::query()
            ->where('processable_type', $processableType)
            ->where('processable_id', $processableId)
            ->pluck('procedure_setting_id')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Check whether a specific internal procedure has been taken for an entity.
     */
    public function isProcedureTaken(
        string $processableType,
        string $processableId,
        string $procedureSettingId,
    ): bool {
        return InternalProcedureTaken::query()
            ->where('processable_type', $processableType)
            ->where('processable_id', $processableId)
            ->where('procedure_setting_id', $procedureSettingId)
            ->exists();
    }

    private function loadStep(?int $stepId): ProcedureSettingStep
    {
        if (! $stepId) {
            throw ProcedureWorkflowException::noActiveStep();
        }

        $step = ProcedureSettingStep::with('actionTakers')->find($stepId);

        if (! $step) {
            throw ProcedureWorkflowException::stepNotFound();
        }

        return $step;
    }

    private function assertIsActionTaker(ProcedureSettingStep $step, string $userId, ?string $createdByUserId = null, array $context = []): void
    {
        $actionTakerType = $step->action_taker_type?->value ?? 'specific_user';

        // For all dynamic types, resolve the full authorized-user list and check membership.
        // - management_hierarchy: single manager OR manager+deputies (deputy_manager type)
        // - specific_procedures:  array of branch/management/job_title/job_role targets
        // - himself:              the original submitter only
        if (in_array($actionTakerType, ['management_hierarchy', 'specific_procedures', 'himself'], true)) {
            $resolvedUserIds = $this->resolver->resolveUsersForStep($step, $createdByUserId, $context);

            if (in_array($userId, $resolvedUserIds, true)) {
                return;
            }

            throw ProcedureWorkflowException::notAuthorized();
        }

        if (! $step->relationLoaded('actionTakers')) {
            $step->load('actionTakers');
        }

        if ($step->actionTakers->isEmpty()) {
            return;
        }

        if (! $step->actionTakers->contains('user_id', $userId)) {
            throw ProcedureWorkflowException::notAuthorized();
        }
    }

    private function collectDescendantIds(string $parentId): array
    {
        $children = ProcedureSetting::query()
            ->where('parent_id', $parentId)
            ->pluck('id')
            ->all();

        $result = $children;
        foreach ($children as $childId) {
            $result = array_merge($result, $this->collectDescendantIds($childId));
        }

        return $result;
    }
}
