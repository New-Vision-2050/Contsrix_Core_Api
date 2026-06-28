<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Listeners;

use Modules\EmployeeTask\DTO\EndTaskDTO;
use Modules\EmployeeTask\DTO\StartTaskDTO;
use Modules\EmployeeTask\Events\EmployeeTaskLifecycleProcessCompleted;
use Modules\EmployeeTask\Models\EmployeeTaskEndRequest;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\EmployeeTask\Models\EmployeeTaskStartRequest;
use Modules\EmployeeTask\Services\EmployeeTaskLifecycleService;
use Modules\Process\Models\Process;
use Modules\Shared\InternalProcessType\Enums\InternalProcessForm;
use Modules\User\Models\User;

final class ExecuteLifecycleActionOnProcessCompleted
{
    public function __construct(
        private readonly EmployeeTaskLifecycleService $lifecycleService,
    ) {}

    public function handle(EmployeeTaskLifecycleProcessCompleted $event): void
    {
        $task    = $event->task;
        $process = $event->process;
        $form    = $this->resolveForm($process);

        if ($form === null) {
            return;
        }

        $metadata = $process->metadata ?? [];

        if ($event->approved) {
            $task->load('user');
            $this->executeApprovedAction($task, $form, $metadata);
        }

        $this->updateLinkedRequest($process, $event->approved, $metadata['review_notes'] ?? null);
    }

    private function executeApprovedAction(
        EmployeeTaskRequest $task,
        InternalProcessForm $form,
        array $metadata,
    ): void {
        match ($form) {
            InternalProcessForm::StartTask,
            InternalProcessForm::ConfirmProjectNotificationPresence => $this->lifecycleService->performStart(
                $task,
                new StartTaskDTO(
                    latitude:  (float) ($metadata['latitude'] ?? 0),
                    longitude: (float) ($metadata['longitude'] ?? 0),
                    internalProcedureSettingId: null,
                    notes:     $metadata['notes'] ?? null,
                ),
                $task->user ?? User::query()->find($task->user_id),
            ),
            InternalProcessForm::EndTask,
            InternalProcessForm::EndProjectNotificationTask => $this->lifecycleService->performEnd(
                $task,
                new EndTaskDTO(
                    latitude:  (float) ($metadata['latitude'] ?? 0),
                    longitude: (float) ($metadata['longitude'] ?? 0),
                    notes:     $metadata['notes'] ?? null,
                    internalProcedureSettingId: null,
                ),
            ),
            default => null,
        };
    }

    private function updateLinkedRequest(Process $process, bool $approved, ?string $notes): void
    {
        $reviewedBy = $process->steps()
            ->whereNotNull('action_by')
            ->orderByDesc('acted_at')
            ->value('action_by');

        $startRequest = EmployeeTaskStartRequest::query()
            ->where('process_id', $process->id)
            ->first();

        if ($startRequest !== null) {
            $startRequest->update([
                'status'                    => $approved ? 'approved' : 'rejected',
                'reviewed_by'               => $reviewedBy,
                'reviewed_at'               => now(),
                'review_notes'              => $notes,
                'current_procedure_step_id' => null,
            ]);
        }

        $endRequest = EmployeeTaskEndRequest::query()
            ->where('process_id', $process->id)
            ->first();

        if ($endRequest !== null) {
            $endRequest->update([
                'status'                    => $approved ? 'approved' : 'rejected',
                'reviewed_by'               => $reviewedBy,
                'reviewed_at'               => now(),
                'review_notes'              => $notes,
                'current_procedure_step_id' => null,
            ]);
        }
    }

    private function resolveForm(Process $process): ?InternalProcessForm
    {
        $procedureSetting = $process->procedureSetting;
        if ($procedureSetting === null || $procedureSetting->form === null) {
            return null;
        }

        try {
            return InternalProcessForm::from($procedureSetting->form);
        } catch (\ValueError) {
            return null;
        }
    }
}
