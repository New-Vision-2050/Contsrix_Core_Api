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
use Modules\ProcedureSetting\Models\ProcedureSetting;
use Modules\ProcedureSetting\Models\ProcedureSettingStep;
use Modules\ProcedureSetting\Notifications\WorkflowActionRequired;
use Modules\ProcedureSetting\Services\ProcedureWorkflowService;
use Modules\Shared\InternalProcessType\Enums\InternalProcessForm;
use Modules\User\Models\User;

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

        $task->loadMissing('user.userProfessionalData');
        $branchId = $task->user?->userProfessionalData?->branch_id;

        return $this->workflow->resolveInternalProcedureSettingByForm(
            ProcedureSettingType::EmployeeTask->value,
            InternalProcessForm::EndTask->value,
            $task->company_id,
            $branchId,
        );
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
            $this->dispatchStepNotifications($firstStep, $userIds);

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

        $context = $task->project_id ? ['project_id' => $task->project_id] : [];
        $result  = $this->workflow->advance(
            $endRequest->current_procedure_step_id,
            $endRequest->procedure_setting_id,
            $adminId,
            $task->user_id,
            $context,
        );

        return DB::transaction(function () use ($endRequest, $task, $result, $adminId, $approvalNotes): EmployeeTaskEndRequest {
            if (! $result->isFinal) {
                $endRequest->update(['current_procedure_step_id' => $result->nextStep->id]);
                return $endRequest->fresh();
            }

            $endRequest->update([
                'status'                    => 'approved',
                'reviewed_by'               => $adminId,
                'reviewed_at'               => now(),
                'review_notes'              => $approvalNotes,
                'current_procedure_step_id' => null,
            ]);

            $this->executeEndTask($task, $endRequest);

            return $endRequest->fresh();
        });
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

        $context = $task->project_id ? ['project_id' => $task->project_id] : [];
        $this->workflow->assertCanReject(
            $endRequest->current_procedure_step_id,
            $adminId,
            $task->user_id,
            $context,
        );

        return DB::transaction(function () use ($endRequest, $adminId, $rejectionReason): EmployeeTaskEndRequest {
            $endRequest->update([
                'status'                    => 'rejected',
                'reviewed_by'               => $adminId,
                'reviewed_at'               => now(),
                'review_notes'              => $rejectionReason,
                'current_procedure_step_id' => null,
            ]);

            return $endRequest->fresh();
        });
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
                $q->where('type', ProcedureSettingType::EmployeeTask->value)
                  ->where('company_id', $task->company_id);
            })
            ->with(['steps' => fn ($q) => $q->orderBy('step_order')])
            ->first();

        if (! $setting) {
            throw EmployeeTaskException::invalidProcedureSetting();
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

    private function dispatchStepNotifications(ProcedureSettingStep $step, array $userIds): void
    {
        $channels = [];
        if ($step->notify_by_email) {
            $channels[] = 'mail';
        }
        if ($step->notify_by_sms) {
            $channels[] = 'sms';
        }

        if ($channels === []) {
            return;
        }

        $users        = User::query()->whereIn('id', $userIds)->get();
        $notification = new WorkflowActionRequired(null, $step, $channels);

        foreach ($users as $user) {
            try {
                $user->notify($notification);
            } catch (\Throwable $e) {
                \Log::error('WorkflowActionRequired notification failed (end request)', [
                    'user_id' => $user->id,
                    'step_id' => $step->id,
                    'error'   => $e->getMessage(),
                ]);
            }
        }
    }
}
