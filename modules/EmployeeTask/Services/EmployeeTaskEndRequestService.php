<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Modules\EmployeeTask\DTO\EndTaskDTO;
use Modules\EmployeeTask\Enums\EmployeeTaskStatus;
use Modules\EmployeeTask\Events\EmployeeTaskNotification;
use Modules\EmployeeTask\Events\InboxCountsUpdated;
use Modules\EmployeeTask\Exceptions\EmployeeTaskException;
use Modules\EmployeeTask\Models\EmployeeTaskEndRequest;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\EmployeeTask\Repositories\EmployeeTaskRepository;
use Modules\EmployeeTask\Repositories\EmployeeTaskSessionRepository;
use Modules\ProcedureSetting\Enums\ProcedureSettingType;
use Modules\ProcedureSetting\Exceptions\ProcedureWorkflowException;
use Modules\ProcedureSetting\Models\ProcedureSetting;
use Modules\ProcedureSetting\Models\ProcedureSettingStep;
use Modules\ProcedureSetting\Services\ProcedureWorkflowService;
use Modules\Process\Models\Process;
use Modules\Process\Services\ProcessWorkflowService;
use Modules\Shared\InternalProcessType\Enums\InternalProcessForm;

/**
 * Handles the "end task with procedure" (انهاء المهمة) flow.
 *
 * When an endTask procedure is configured, the employee's end request goes
 * through workflow approval. On final approval, the task session is closed
 * and the task is marked as completed.
 *
 * When no procedure is configured, EmployeeTaskLifecycleService ends the task directly.
 */
final class EmployeeTaskEndRequestService
{
    public function __construct(
        private readonly EmployeeTaskRepository        $taskRepo,
        private readonly EmployeeTaskSessionRepository $sessionRepo,
        private readonly ProcedureWorkflowService      $workflow,
        private readonly EmployeeTaskRequestService    $requestService,
        private readonly ProcessWorkflowService        $processService,
    ) {}

    /**
     * Resolve the endTask procedure setting for a task.
     * Returns null if no procedure is configured (auto-approve/direct end).
     */
    public function resolveEndTaskProcedure(
        EmployeeTaskRequest $task,
        ?string $internalProcedureSettingId = null,
    ): ?ProcedureSetting {
        if ($internalProcedureSettingId) {
            return $this->loadInternalProcedureSetting($internalProcedureSettingId, $task);
        }

        $formKey = $task->is_project_notification
            ? InternalProcessForm::EndProjectNotificationTask->value
            : InternalProcessForm::EndTask->value;

        if ($task->procedure_setting_id !== null) {
            $setting = ProcedureSetting::query()
                ->where('parent_id', $task->procedure_setting_id)
                ->where('form', $formKey)
                ->where('is_active', true)
                ->with(['steps' => fn ($q) => $q->orderBy('step_order')])
                ->first();

            if ($setting !== null && $setting->steps->isNotEmpty()) {
                return $setting;
            }
        }

        $task->loadMissing('user.userProfessionalData');
        $branchId = $task->user?->userProfessionalData?->branch_id;

        return $this->workflow->resolveInternalProcedureSettingByForm(
            $task->procedureSettingType()->value,
            $formKey,
            $task->company_id,
            $branchId,
        );
    }

    /**
     * Create a pending end request linked to an existing Process snapshot.
     */
    public function createFromProcess(
        EmployeeTaskRequest $task,
        EndTaskDTO $dto,
        ProcedureSetting $procedureSetting,
        Process $process,
    ): EmployeeTaskEndRequest {
        $snapshot = $process->template_snapshot ?? [];
        $firstStepId = $snapshot[0]['step_id'] ?? null;

        return EmployeeTaskEndRequest::query()->create([
            'employee_task_request_id' => $task->id,
            'company_id'               => $task->company_id,
            'procedure_setting_id'     => $procedureSetting->id,
            'process_id'               => $process->id,
            'requested_by'             => $task->user_id,
            'latitude'                 => $dto->latitude,
            'longitude'                => $dto->longitude,
            'notes'                    => $dto->notes,
            'status'                   => 'pending',
            'current_procedure_step_id' => $firstStepId,
        ]);
    }

    /**
     * Create a pending end request and notify action takers.
     * Called when a procedure setting has been resolved for the task.
     */
    public function create(
        EmployeeTaskRequest $task,
        EndTaskDTO $dto,
        ProcedureSetting $procedureSetting,
    ): EmployeeTaskEndRequest {
        return DB::transaction(function () use ($task, $dto, $procedureSetting): EmployeeTaskEndRequest {
            $firstStep = $this->workflow->resolveFirstStepBySettingId($procedureSetting->id);

            if ($firstStep === null) {
                throw ProcedureWorkflowException::noStepsConfigured();
            }

            $endRequest = EmployeeTaskEndRequest::query()->create([
                'employee_task_request_id' => $task->id,
                'company_id'               => $task->company_id,
                'procedure_setting_id'     => $procedureSetting->id,
                'requested_by'             => $task->user_id,
                'latitude'                 => $dto->latitude,
                'longitude'                => $dto->longitude,
                'notes'                    => $dto->notes,
                'status'                   => 'pending',
                'current_procedure_step_id' => $firstStep->id,
            ]);

            $context = $task->project_id ? ['project_id' => $task->project_id] : [];
            $userIds = $this->workflow->resolveActionTakerUserIdsForStep($firstStep, $task->user_id, $context);

            $this->broadcastTaskNotification($task, $firstStep, $userIds);
            $this->requestService->broadcastInboxCounts($userIds);

            return $endRequest;
        });
    }

    /**
     * Admin approves an end request through the workflow.
     * On final step, closes the active session and marks the task as completed.
     */
    public function approve(string $endRequestId, string $adminId, ?string $approvalNotes = null): EmployeeTaskEndRequest
    {
        $endRequest = $this->findOrFail($endRequestId);
        $task       = $this->taskRepo->findById($endRequest->employee_task_request_id);

        if (! $task) {
            throw EmployeeTaskException::notFound();
        }

        if ($endRequest->status !== 'pending') {
            throw EmployeeTaskException::endRequestAlreadyResolved();
        }

        $process = $endRequest->process;
        if ($process === null) {
            throw EmployeeTaskException::endRequestNotFound();
        }

        $currentStep = $this->processService->getCurrentStep($process);
        if (! $currentStep) {
            throw EmployeeTaskException::endRequestNotFound();
        }

        $this->processService->approveStep($currentStep->id);

        return $endRequest->fresh();
    }

    /**
     * Admin rejects an end request.
     * The task remains in its current status and the employee can re-submit.
     */
    public function reject(string $endRequestId, string $adminId, string $rejectionReason): EmployeeTaskEndRequest
    {
        $endRequest = $this->findOrFail($endRequestId);
        $task       = $this->taskRepo->findById($endRequest->employee_task_request_id);

        if (! $task) {
            throw EmployeeTaskException::notFound();
        }

        if ($endRequest->status !== 'pending') {
            throw EmployeeTaskException::endRequestAlreadyResolved();
        }

        $process = $endRequest->process;
        if ($process === null) {
            throw EmployeeTaskException::endRequestNotFound();
        }

        $currentStep = $this->processService->getCurrentStep($process);
        if (! $currentStep) {
            throw EmployeeTaskException::endRequestNotFound();
        }

        $this->processService->rejectStep($currentStep->id);

        return $endRequest->fresh();
    }

    public function findOrFail(string $id): EmployeeTaskEndRequest
    {
        $endRequest = EmployeeTaskEndRequest::query()
            ->with(['task.user', 'requestedByUser', 'currentProcedureStep.actionTakers.user'])
            ->find($id);

        if (! $endRequest) {
            throw EmployeeTaskException::endRequestNotFound();
        }

        return $endRequest;
    }

    // ─── private ─────────────────────────────────────────────────────────────

    /**
     * Execute the actual end-task business logic once the workflow is approved.
     * Mirrors the logic in EmployeeTaskLifecycleService::end().
     */
    private function executeEndTask(EmployeeTaskRequest $task, EmployeeTaskEndRequest $endRequest): void
    {
        $timezone = $task->timezone ?: config('app.timezone') ?: 'Asia/Riyadh';
        $now      = CarbonImmutable::now($timezone);

        $activeSession = $this->sessionRepo->findActiveByTask($task->id);

        if ($activeSession) {
            $sessionStart    = CarbonImmutable::parse($activeSession->start_time, $timezone);
            $durationMinutes = max(0, (int) $sessionStart->diffInMinutes($now));

            $this->sessionRepo->closeSession($activeSession, [
                'end_time'         => $now->format('Y-m-d H:i:s'),
                'duration_minutes' => $durationMinutes,
                'end_latitude'     => (float) $endRequest->latitude,
                'end_longitude'    => (float) $endRequest->longitude,
                'source'           => 'manual',
            ]);
        }

        $totalSessionMinutes = $this->sessionRepo->sumCompletedMinutes($task->id);
        $timeFrom            = CarbonImmutable::parse($task->time_from, $timezone);
        $totalElapsedMinutes = max(0, (int) $timeFrom->diffInMinutes($now));
        $totalPauseMinutes   = max(0, $totalElapsedMinutes - $totalSessionMinutes);
        $totalTaskHours      = round($totalSessionMinutes / 60, 2);

        $this->taskRepo->update($task, [
            'status'              => EmployeeTaskStatus::Completed->value,
            'time_to'             => $now->format('Y-m-d H:i:s'),
            'total_task_hours'    => $totalTaskHours,
            'total_pause_minutes' => $totalPauseMinutes,
            'shift_end_method'    => 'manual',
            'end_location'        => ['latitude' => (float) $endRequest->latitude, 'longitude' => (float) $endRequest->longitude],
            'notes'               => $endRequest->notes ?? $task->notes,
        ]);
    }

    private function loadInternalProcedureSetting(string $id, EmployeeTaskRequest $task): ?ProcedureSetting
    {
        $setting = ProcedureSetting::query()
            ->where('id', $id)
            ->whereNotNull('form')
            ->whereHas('parent', function ($q) use ($task) {
                $q->where('type', $task->procedureSettingType()->value)
                  ->where('company_id', $task->company_id);
            })
            ->with(['steps' => fn ($q) => $q->orderBy('step_order')])
            ->first();

        if (! $setting) {
            throw EmployeeTaskException::invalidProcedureSetting();
        }

        // No steps configured → auto-approve (return null so caller skips workflow)
        if ($setting->steps->isEmpty()) {
            return null;
        }

        return $setting;
    }

    private function broadcastTaskNotification(EmployeeTaskRequest $task, ProcedureSettingStep $currentStep, array $userIds = []): void
    {
        $task->load(['user']);

        if ($userIds === []) {
            $currentStep->load(['actionTakers.user']);
        }

        \Log::info('Broadcasting EmployeeTaskNotification (end request)', [
            'task_id'  => $task->id,
            'step_id'  => $currentStep->id,
            'user_ids' => $userIds,
        ]);

        event(new EmployeeTaskNotification($task, $currentStep, $userIds));
    }

}
