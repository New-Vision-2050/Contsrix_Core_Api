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
use Modules\Project\ProjectManagement\Models\ProjectProcedureSetting;
use Modules\Project\ProjectManagement\Models\ProjectRequirement;
use Modules\Project\ProjectManagement\Models\ProjectRequirementSubmission;

final class ProjectRequirementSubmissionWorkflowService
{
    public function __construct(
        private readonly WorkflowEngine $engine,
        private readonly ProcessWorkflowService $processWorkflowService,
        private readonly AttachmentArchiveDeliveryService $archiveDeliveryService,
    ) {}

    public function startForSubmission(
        ProjectRequirementSubmission $submission,
        ProjectRequirement $requirement,
    ): ?Process {
        $procedureSetting = $this->resolveProjectProcedureSetting($requirement);
        if ($procedureSetting === null) {
            return null;
        }

        $existing = Process::query()
            ->where('processable_id', $submission->id)
            ->where('processable_type', ProjectRequirementSubmission::PROCESSABLE_TYPE)
            ->orderBy('sort_order')
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $result = $this->engine->startWorkflow(
            processableType: ProjectRequirementSubmission::PROCESSABLE_TYPE,
            processableId: $submission->id,
            type: ProjectProcedureSetting::PROCEDURE_TYPE,
            formKey: null,
            companyId: (string) $requirement->company_id,
            branchId: null,
            createdByUserId: Auth::check() ? (string) Auth::id() : null,
            context: $this->contextFor($submission, $requirement),
            metadata: $this->metadataFor($submission, $requirement),
            resolvedSetting: $procedureSetting,
        );

        return $result->activeProcess;
    }

    public function hasActiveWorkflow(ProjectRequirementSubmission $submission): bool
    {
        return Process::query()
            ->where('processable_id', $submission->id)
            ->where('processable_type', ProjectRequirementSubmission::PROCESSABLE_TYPE)
            ->where('status', ProcessStatus::InProgress)
            ->exists();
    }

    public function actOnPendingStepForCurrentUser(
        ProjectRequirementSubmission $submission,
        string $action,
    ): Process {
        if (! Auth::check()) {
            abort(403);
        }

        $process = Process::query()
            ->where('processable_id', $submission->id)
            ->where('processable_type', ProjectRequirementSubmission::PROCESSABLE_TYPE)
            ->where('status', ProcessStatus::InProgress)
            ->first();

        if ($process === null) {
            abort(422, 'No active process found for this requirement submission.');
        }

        $step = $this->findPendingStepForActor($process, (string) Auth::id());
        if ($step === null) {
            abort(422, 'No pending process step assigned to you for this requirement submission.');
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

    public function deliverToArchive(ProjectRequirementSubmission $submission, ?Process $process = null): void
    {
        $uploaderCompanyId = null;
        if ($process !== null) {
            $metadata = $process->metadata ?? [];
            $uploaderCompanyId = isset($metadata['uploader_company_id'])
                ? (string) $metadata['uploader_company_id']
                : null;
        }

        if ($uploaderCompanyId === null || $uploaderCompanyId === '') {
            $existing = Process::query()
                ->where('processable_id', $submission->id)
                ->where('processable_type', ProjectRequirementSubmission::PROCESSABLE_TYPE)
                ->orderBy('sort_order')
                ->first();
            $uploaderCompanyId = isset($existing?->metadata['uploader_company_id'])
                ? (string) $existing->metadata['uploader_company_id']
                : null;
        }

        $this->archiveDeliveryService->deliverRequirementSubmission(
            $submission,
            $uploaderCompanyId !== '' ? $uploaderCompanyId : null,
        );
    }

    private function resolveProjectProcedureSetting(ProjectRequirement $requirement): ?ProcedureSetting
    {
        if ($requirement->procedure_setting_id === null || $requirement->procedure_setting_id === '') {
            return null;
        }

        $requirement->loadMissing('procedureSetting');
        $procedureSetting = $requirement->procedureSetting;

        if (
            $procedureSetting === null
            || (string) $procedureSetting->type !== ProjectProcedureSetting::PROCEDURE_TYPE
        ) {
            return null;
        }

        $linkedToProject = ProjectProcedureSetting::query()
            ->withoutGlobalScopes()
            ->where('company_id', (string) $requirement->company_id)
            ->where('project_id', (string) $requirement->project_id)
            ->where('procedure_setting_id', (string) $requirement->procedure_setting_id)
            ->exists();

        return $linkedToProject ? $procedureSetting : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function contextFor(ProjectRequirementSubmission $submission, ProjectRequirement $requirement): array
    {
        $receiverCompanyIds = $this->receiverCompanyIds($requirement);

        return [
            'project_id' => $submission->project_id,
            'project_requirement_id' => $submission->project_requirement_id,
            'uploader_company_id' => tenant('id') !== null ? (string) tenant('id') : null,
            'receiver_company_id' => $receiverCompanyIds[0] ?? null,
            'receiver_company_ids' => $receiverCompanyIds,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function metadataFor(ProjectRequirementSubmission $submission, ProjectRequirement $requirement): array
    {
        return [
            'project_id' => $submission->project_id,
            'project_requirement_id' => $submission->project_requirement_id,
            'project_requirement_submission_id' => $submission->id,
            'procedure_setting_id' => $requirement->procedure_setting_id,
            'uploader_company_id' => tenant('id') !== null ? (string) tenant('id') : null,
            'receiver_company_ids' => $this->receiverCompanyIds($requirement),
        ];
    }

    /**
     * @return list<string>
     */
    private function receiverCompanyIds(ProjectRequirement $requirement): array
    {
        $requirement->loadMissing('receiverCompanies');

        return $requirement->receiverCompanies
            ->pluck('id')
            ->map(static fn (mixed $id): string => (string) $id)
            ->filter(static fn (string $id): bool => $id !== '')
            ->unique()
            ->values()
            ->all();
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
