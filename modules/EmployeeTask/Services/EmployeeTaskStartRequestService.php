<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Modules\EmployeeTask\DTO\StartTaskDTO;
use Modules\EmployeeTask\Enums\EmployeeTaskStatus;
use Modules\EmployeeTask\Events\EmployeeTaskNotification;
use Modules\EmployeeTask\Exceptions\EmployeeTaskException;
use Modules\EmployeeTask\Jobs\AutoCloseTaskAtDurationExpiryJob;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\EmployeeTask\Models\EmployeeTaskStartRequest;
use Modules\EmployeeTask\Repositories\EmployeeTaskRepository;
use Modules\EmployeeTask\Repositories\EmployeeTaskSessionRepository;
use Modules\EmployeeTask\Services\EmployeeTaskLocationService;
use Modules\ProcedureSetting\Enums\ProcedureSettingType;
use Modules\ProcedureSetting\Exceptions\ProcedureWorkflowException;
use Modules\ProcedureSetting\Models\ProcedureSetting;
use Modules\ProcedureSetting\Models\ProcedureSettingStep;
use Modules\ProcedureSetting\Notifications\WorkflowActionRequired;
use Modules\ProcedureSetting\Services\ProcedureWorkflowService;
use Modules\ProcedureSetting\Services\WorkflowPushNotificationService;
use Modules\Shared\InternalProcessType\Enums\InternalProcessForm;
use Modules\User\Models\User;

/**
 * Handles the "start task with procedure" (بدء المهمة) flow.
 *
 * When a startTask procedure is configured, the employee's start request goes
 * through workflow approval. On final approval, the task is marked as in_progress
 * and a session is created.
 *
 * When no procedure is configured, EmployeeTaskLifecycleService starts the task directly.
 */
final class EmployeeTaskStartRequestService
{
    public function __construct(
        private readonly EmployeeTaskRepository        $taskRepo,
        private readonly EmployeeTaskSessionRepository $sessionRepo,
        private readonly ProcedureWorkflowService      $workflow,
        private readonly EmployeeTaskRequestService    $requestService,
        private readonly EmployeeTaskLocationService   $locationService,
    ) {}

    /**
     * Resolve the startTask procedure setting for a task.
     * Returns null if no procedure is configured (auto-approve/direct start).
     */
    public function resolveStartTaskProcedure(
        EmployeeTaskRequest $task,
        ?string $internalProcedureSettingId = null,
    ): ?ProcedureSetting {
        if ($internalProcedureSettingId) {
            return $this->loadInternalProcedureSetting($internalProcedureSettingId, $task);
        }

        $formKey = InternalProcessForm::StartTask->value;

        // Prefer the task's snapshot parent procedure setting, then fall back to
        // company/branch resolution.
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

            // If the snapshot parent doesn't have the requested form, fall back.
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
     * Create a pending start request and notify action takers.
     * Called when a procedure setting has been resolved for the task.
     */
    public function create(
        EmployeeTaskRequest $task,
        StartTaskDTO $dto,
        ProcedureSetting $procedureSetting,
    ): EmployeeTaskStartRequest {
        return DB::transaction(function () use ($task, $dto, $procedureSetting): EmployeeTaskStartRequest {
            $firstStep = $this->workflow->resolveFirstStepBySettingId($procedureSetting->id);

            if ($firstStep === null) {
                throw ProcedureWorkflowException::noStepsConfigured();
            }

            $startRequest = EmployeeTaskStartRequest::query()->create([
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

            return $startRequest;
        });
    }

    /**
     * Admin approves a start request through the workflow.
     * On final step, marks the task as in_progress and creates a session.
     */
    public function approve(string $startRequestId, string $adminId, ?string $approvalNotes = null): EmployeeTaskStartRequest
    {
        $startRequest = $this->findOrFail($startRequestId);
        $task       = $this->taskRepo->findById($startRequest->employee_task_request_id);

        if (! $task) {
            throw EmployeeTaskException::notFound();
        }

        if ($startRequest->status !== 'pending') {
            throw EmployeeTaskException::startRequestAlreadyResolved();
        }

        $context = $task->project_id ? ['project_id' => $task->project_id] : [];
        $result  = $this->workflow->advance(
            $startRequest->current_procedure_step_id,
            $startRequest->procedure_setting_id,
            $adminId,
            $task->user_id,
            $context,
            processableType: $task->procedureSettingType()->value,
            processableId: $task->id,
        );

        return DB::transaction(function () use ($startRequest, $task, $result, $adminId, $approvalNotes): EmployeeTaskStartRequest {
            if (! $result->isFinal) {
                $startRequest->update(['current_procedure_step_id' => $result->nextStep->id]);
                return $startRequest->fresh();
            }

            $startRequest->update([
                'status'                    => 'approved',
                'reviewed_by'               => $adminId,
                'reviewed_at'               => now(),
                'review_notes'              => $approvalNotes,
                'current_procedure_step_id' => null,
            ]);

            $this->executeStartTask($task, $startRequest);

            return $startRequest->fresh();
        });
    }

    /**
     * Admin rejects a start request.
     * The task remains in its approved status and the employee can re-submit.
     */
    public function reject(string $startRequestId, string $adminId, string $rejectionReason): EmployeeTaskStartRequest
    {
        $startRequest = $this->findOrFail($startRequestId);
        $task       = $this->taskRepo->findById($startRequest->employee_task_request_id);

        if (! $task) {
            throw EmployeeTaskException::notFound();
        }

        if ($startRequest->status !== 'pending') {
            throw EmployeeTaskException::startRequestAlreadyResolved();
        }

        $context = $task->project_id ? ['project_id' => $task->project_id] : [];
        $this->workflow->assertCanReject(
            $startRequest->current_procedure_step_id,
            $adminId,
            $task->user_id,
            $context,
        );

        return DB::transaction(function () use ($startRequest, $adminId, $rejectionReason): EmployeeTaskStartRequest {
            $startRequest->update([
                'status'                    => 'rejected',
                'reviewed_by'               => $adminId,
                'reviewed_at'               => now(),
                'review_notes'              => $rejectionReason,
                'current_procedure_step_id' => null,
            ]);

            return $startRequest->fresh();
        });
    }

    public function findOrFail(string $id): EmployeeTaskStartRequest
    {
        $startRequest = EmployeeTaskStartRequest::query()
            ->with(['task.user', 'requestedByUser', 'currentProcedureStep.actionTakers.user'])
            ->find($id);

        if (! $startRequest) {
            throw EmployeeTaskException::startRequestNotFound();
        }

        return $startRequest;
    }

    // ─── private ─────────────────────────────────────────────────────────────

    /**
     * Execute the actual start-task business logic once the workflow is approved.
     * Mirrors the logic in EmployeeTaskLifecycleService::start().
     */
    private function executeStartTask(EmployeeTaskRequest $task, EmployeeTaskStartRequest $startRequest): void
    {
        $task->load('user.userProfessionalData');
        $timezone     = $this->resolveTimezone($task);
        $radiusMeters = $this->locationService->snapshotRadiusFromConstraint($task->user);
        $now          = CarbonImmutable::now($timezone);

        $this->taskRepo->update($task, [
            'status'         => EmployeeTaskStatus::InProgress->value,
            'time_from'      => $now->format('Y-m-d H:i:s'),
            'radius_meters'  => $radiusMeters,
            'timezone'       => $timezone,
            'start_location' => ['latitude' => (float) $startRequest->latitude, 'longitude' => (float) $startRequest->longitude],
        ]);

        $this->sessionRepo->create([
            'employee_task_request_id' => $task->id,
            'company_id'               => $task->company_id,
            'start_time'               => $now->format('Y-m-d H:i:s'),
            'start_latitude'           => (float) $startRequest->latitude,
            'start_longitude'          => (float) $startRequest->longitude,
            'source'                   => 'manual',
        ]);

        $task->refresh();

        $maxOverTimeHours = $this->resolveMaxOverTimeHours($task);
        $deadline = $now
            ->addHours((float) $task->duration_hours)
            ->addHours($maxOverTimeHours);

        $closeAtIso = $now->addHours((float) $task->duration_hours)->toIso8601String();

        AutoCloseTaskAtDurationExpiryJob::dispatch(
            taskId:     $task->id,
            companyId:  $task->company_id,
            closeAtIso: $closeAtIso,
        )->delay($deadline);
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

    private function resolveTimezone(EmployeeTaskRequest $task): string
    {
        $timezones = $task->user?->userProfessionalData?->branch?->address?->country?->timezones;
        if (is_array($timezones) && isset($timezones[0]['zoneName'])) {
            return $timezones[0]['zoneName'];
        }
        return getTimeZoneBranchByRequest() ?? config('app.timezone') ?? 'Asia/Riyadh';
    }

    private function resolveMaxOverTimeHours(EmployeeTaskRequest $task): float
    {
        $constraint = $task->user?->userProfessionalData?->attendanceConstraint;

        return (float) ($constraint?->max_over_time ?? 0.0);
    }

    private function broadcastTaskNotification(EmployeeTaskRequest $task, ProcedureSettingStep $currentStep, array $userIds = []): void
    {
        $task->load(['user']);

        if ($userIds === []) {
            $currentStep->load(['actionTakers.user']);
        }

        \Log::info('Broadcasting EmployeeTaskNotification (start request)', [
            'task_id'  => $task->id,
            'step_id'  => $currentStep->id,
            'user_ids' => $userIds,
        ]);

        event(new EmployeeTaskNotification($task, $currentStep, $userIds));
    }

    private function dispatchStepNotifications(ProcedureSettingStep $step, array $userIds): void
    {
        WorkflowPushNotificationService::sendForStep($step, $userIds);

        $channels = [];
        if ($step->notify_by_email) {
            $channels[] = 'mail';
        }
        if ($step->notify_by_sms) {
            $channels[] = 'sms';
        }
        if ($step->notify_by_whatsapp) {
            $channels[] = 'whatsapp';
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
                \Log::error('WorkflowActionRequired notification failed (start request)', [
                    'user_id' => $user->id,
                    'step_id' => $step->id,
                    'error'   => $e->getMessage(),
                ]);
            }
        }
    }
}
