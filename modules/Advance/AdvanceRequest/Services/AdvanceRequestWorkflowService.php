<?php

declare(strict_types=1);

namespace Modules\Advance\AdvanceRequest\Services;

use Illuminate\Support\Facades\Auth;
use Modules\Advance\AdvanceRequest\Models\AdvanceRequest;
use Modules\ProcedureSetting\Enums\ProcedureSettingType;
use Modules\ProcedureSetting\Services\WorkflowEngine;
use Modules\Process\Enums\ProcessStatus;
use Modules\Process\Enums\ProcessStepStatus;
use Modules\Process\Models\Process;
use Modules\Process\Models\ProcessStep;
use Modules\Process\Services\ProcessWorkflowService;
use Modules\Shared\InternalProcessType\Enums\InternalProcessForm;

/**
 * Bridges advance requests with the procedure-workflow engine.
 *
 * - On create: starts the `createAdvanceRequest` workflow resolved from the company /
 *   branch procedure settings (seeded per company by InternalProcedureSettingsSeeder).
 * - On approve/reject: delegates to the central ProcessWorkflowService; the
 *   AdvanceRequest model finalizes its own status via the onAllProcessesCompleted /
 *   onProcessFailed hooks.
 *
 * When no workflow is configured (or no step resolves to a user), the engine reports
 * auto-approve and the request is approved immediately.
 */
class AdvanceRequestWorkflowService
{
    public const PROCESSABLE_TYPE = 'advance_request';

    public function __construct(
        private readonly WorkflowEngine $engine,
        private readonly ProcessWorkflowService $processService,
    ) {}

    /**
     * Start the approval workflow for a freshly created advance request.
     *
     * @return ?Process The active process, or null when auto-approved.
     */
    public function startForAdvanceRequest(AdvanceRequest $advanceRequest, ?string $branchId): ?Process
    {
        $result = $this->engine->startWorkflow(
            processableType: self::PROCESSABLE_TYPE,
            processableId: (string) $advanceRequest->id,
            type: ProcedureSettingType::AdvanceRequest->value,
            formKey: InternalProcessForm::CreateAdvanceRequest->value,
            companyId: (string) $advanceRequest->company_id,
            branchId: $branchId,
            createdByUserId: (string) $advanceRequest->requested_by,
        );

        return $result->autoApprove ? null : $result->activeProcess;
    }

    /**
     * Approve the pending workflow step assigned to the current user.
     */
    public function approveForCurrentUser(string $advanceRequestId): ProcessStep
    {
        $step = $this->pendingStepForCurrentUser($advanceRequestId);

        abort_if($step === null, 422, 'No pending workflow step assigned to you for this advance request.');

        return $this->processService->approveStep((string) $step->id);
    }

    /**
     * Reject the pending workflow step assigned to the current user.
     */
    public function rejectForCurrentUser(string $advanceRequestId): ProcessStep
    {
        $step = $this->pendingStepForCurrentUser($advanceRequestId);

        abort_if($step === null, 422, 'No pending workflow step assigned to you for this advance request.');

        return $this->processService->rejectStep((string) $step->id);
    }

    /**
     * Fail any open workflow processes (used when the employee cancels the request).
     */
    public function cancelOpenProcesses(AdvanceRequest $advanceRequest, ?string $actorId): void
    {
        $processes = Process::query()
            ->where('processable_type', self::PROCESSABLE_TYPE)
            ->where('processable_id', (string) $advanceRequest->id)
            ->whereIn('status', [ProcessStatus::Pending, ProcessStatus::InProgress])
            ->get();

        foreach ($processes as $process) {
            $process->steps()
                ->where('status', ProcessStepStatus::Pending)
                ->update([
                    'status' => ProcessStepStatus::Rejected,
                    'action_by' => $actorId,
                    'acted_at' => now(),
                ]);

            $process->update(['status' => ProcessStatus::Failed]);
        }
    }

    /**
     * Whether the current user has a pending step for this request (inbox helper).
     */
    public function currentUserCanAct(AdvanceRequest $advanceRequest): bool
    {
        return $this->pendingStepForCurrentUser((string) $advanceRequest->id) !== null;
    }

    private function pendingStepForCurrentUser(string $advanceRequestId): ?ProcessStep
    {
        $actorId = (string) Auth::id();

        $processes = Process::query()
            ->where('processable_type', self::PROCESSABLE_TYPE)
            ->where('processable_id', $advanceRequestId)
            ->where('status', ProcessStatus::InProgress)
            ->with(['steps' => fn ($query) => $query->where('status', ProcessStepStatus::Pending)->orderBy('template_step_order')])
            ->orderBy('sort_order')
            ->get();

        foreach ($processes as $process) {
            foreach ($process->steps as $step) {
                $authorized = $step->authorized_user_ids ?? [(string) $step->assigned_user_id];

                if (in_array($actorId, $authorized, true)) {
                    return $step;
                }
            }
        }

        return null;
    }
}
