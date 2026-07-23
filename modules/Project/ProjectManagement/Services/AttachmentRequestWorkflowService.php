<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Services;

use Illuminate\Support\Facades\Auth;
use Modules\ProcedureSetting\Models\ProcedureSetting;
use Modules\ProcedureSetting\Services\WorkflowEngine;
use Modules\Process\Enums\ProcessStatus;
use Modules\Process\Enums\ProcessStepStatus;
use Modules\Process\Models\Process;
use Modules\Process\Models\ProcessStep;
use Modules\Process\Services\ProcessWorkflowService;
use Modules\Project\ProjectManagement\Models\AttachmentRequest;
use Modules\Project\ProjectManagement\Models\ProjectProcedureSetting;

final class AttachmentRequestWorkflowService
{
    public function __construct(
        private readonly WorkflowEngine $engine,
        private readonly ProcessWorkflowService $processWorkflowService,
    ) {}

    public function startForAttachmentRequest(
        AttachmentRequest $request,
        ProcedureSetting $procedureSetting,
    ): ?Process {
        $existing = Process::query()
            ->where('processable_id', $request->id)
            ->where('processable_type', AttachmentRequest::PROCESSABLE_TYPE)
            ->orderBy('sort_order')
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $result = $this->engine->startWorkflow(
            processableType: AttachmentRequest::PROCESSABLE_TYPE,
            processableId: $request->id,
            type: ProjectProcedureSetting::PROCEDURE_TYPE,
            formKey: null,
            companyId: (string) $request->sender_company_id,
            branchId: null,
            createdByUserId: $request->created_by_user_id,
            context: $this->contextFor($request),
            metadata: $this->metadataFor($request),
            resolvedSetting: $procedureSetting,
        );

        return $result->activeProcess;
    }

    public function hasActiveWorkflow(AttachmentRequest $request): bool
    {
        return Process::query()
            ->where('processable_id', $request->id)
            ->where('processable_type', AttachmentRequest::PROCESSABLE_TYPE)
            ->where('status', ProcessStatus::InProgress)
            ->exists();
    }

    public function actOnPendingStepForCurrentUser(AttachmentRequest $request, string $action): Process
    {
        if (! Auth::check()) {
            abort(403);
        }

        $process = Process::query()
            ->where('processable_id', $request->id)
            ->where('processable_type', AttachmentRequest::PROCESSABLE_TYPE)
            ->where('status', ProcessStatus::InProgress)
            ->first();

        if ($process === null) {
            abort(422, 'No active process found for this attachment request.');
        }

        $step = $this->findPendingStepForActor($process, (string) Auth::id());

        if ($step === null) {
            abort(422, 'No pending process step assigned to you for this attachment request.');
        }

        match ($action) {
            'approve' => $this->processWorkflowService->approveStep((string) $step->id),
            'reject' => $this->processWorkflowService->rejectStep((string) $step->id),
            default => abort(422, 'Invalid process step action.'),
        };

        return Process::query()
            ->with('steps')
            ->findOrFail($process->id);
    }

    /**
     * @return array<string, mixed>
     */
    private function contextFor(AttachmentRequest $request): array
    {
        return [
            'project_id' => $request->project_id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function metadataFor(AttachmentRequest $request): array
    {
        return [
            'project_id' => $request->project_id,
            'procedure_setting_id' => $request->procedure_setting_id,
        ];
    }

    private function findPendingStepForActor(Process $process, string $actorId): ?ProcessStep
    {
        $pendingSteps = ProcessStep::query()
            ->where('process_id', $process->id)
            ->where('status', ProcessStepStatus::Pending)
            ->get();

        foreach ($pendingSteps as $step) {
            if (in_array($actorId, $this->getAuthorizedUsersForStep($process, $step), true)) {
                return $step;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function getAuthorizedUsersForStep(Process $process, ProcessStep $step): array
    {
        if ($step->authorized_user_ids !== null) {
            return $step->authorized_user_ids;
        }

        $snapshot = $process->template_snapshot ?? [];
        foreach ($snapshot as $row) {
            if ((int) ($row['step_id'] ?? 0) === (int) $step->step_id) {
                return $row['authorized_user_ids'] ?? [(string) $row['assigned_user_id']];
            }
        }

        return [(string) $step->assigned_user_id];
    }
}
