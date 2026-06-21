<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\EmployeeTask\DTO\CreateExtensionRequestDTO;
use Modules\EmployeeTask\Enums\EmployeeTaskStatus;
use Modules\EmployeeTask\Events\EmployeeTaskNotification;
use Modules\EmployeeTask\Exceptions\EmployeeTaskException;
use Modules\EmployeeTask\Models\EmployeeTaskExtensionRequest;
use Modules\EmployeeTask\Repositories\EmployeeTaskRepository;
use Modules\ProcedureSetting\Enums\ProcedureSettingType;
use Modules\ProcedureSetting\Events\WorkflowProcedureTaken;
use Modules\ProcedureSetting\Models\ProcedureSetting;
use Modules\ProcedureSetting\Services\ProcedureWorkflowService;
use Modules\EmployeeTask\Events\InboxCountsUpdated;
use Modules\ProcedureSetting\Notifications\WorkflowActionRequired;
use Modules\User\Models\User;
final class EmployeeTaskExtensionService
{
    public function __construct(
        private readonly EmployeeTaskRepository $taskRepo,
        private readonly ProcedureWorkflowService $workflow,
        private readonly EmployeeTaskRequestService $requestService,
    ) {}

    /**
     * Request an extension for an active task.
     *
     * Uses the dedicated employee_task_extension procedure setting (not the parent task).
     */
    public function requestExtension(CreateExtensionRequestDTO $dto): EmployeeTaskExtensionRequest
    {
        $task = $this->taskRepo->findById($dto->taskId);

        if (!$task) {
            throw EmployeeTaskException::notFound();
        }

        $allowedStatuses = [EmployeeTaskStatus::InProgress->value, EmployeeTaskStatus::Paused->value];
        if (!in_array($task->status, $allowedStatuses, true)) {
            throw EmployeeTaskException::extensionNotAllowed();
        }

        if ($task->hasPendingExtension()) {
            throw EmployeeTaskException::pendingExtensionExists();
        }

        return DB::transaction(function () use ($task, $dto): EmployeeTaskExtensionRequest {
            $data = array_merge(
                $dto->toArray(),
                ['company_id' => $task->company_id]
            );

            $procedureSetting = $dto->internalProcedureSettingId
                ? $this->loadInternalProcedureSetting($dto->internalProcedureSettingId, $task)
                : $this->resolveExtensionProcedureSetting($task);
            $data['procedure_setting_id'] = $procedureSetting?->id;

            if ($procedureSetting === null) {
                $data['status'] = 'approved';
                $data['current_procedure_step_id'] = null;
                $data['reviewed_at'] = now();
            } else {
                $data['status'] = 'pending';
                $firstStep = $this->workflow->resolveFirstStepBySettingId($procedureSetting->id);
                if ($firstStep === null) {
                    $data['status'] = 'approved';
                    $data['current_procedure_step_id'] = null;
                    $data['reviewed_at'] = now();
                    $procedureSetting = null;
                } else {
                    $data['current_procedure_step_id'] = $firstStep->id;
                }
            }

            $extension = EmployeeTaskExtensionRequest::query()->create($data);
            $task->update(['last_extension_status' => 'extension_pending']);

            if ($procedureSetting === null && $dto->internalProcedureSettingId) {
                event(new WorkflowProcedureTaken(
                    'employee_task',
                    $task->id,
                    $dto->internalProcedureSettingId,
                    $dto->userId,
                ));
            }

            if ($procedureSetting !== null) {
                $firstStep = $this->workflow->resolveFirstStepBySettingId($procedureSetting->id);
                $context   = $task->project_id ? ['project_id' => $task->project_id] : [];
                $userIds   = $this->workflow->resolveActionTakerUserIdsForStep($firstStep, $task->user_id, $context);
                $this->broadcastTaskNotification($task, $firstStep, $userIds);
                $this->requestService->broadcastInboxCounts($userIds);
                $this->dispatchStepNotifications($firstStep, $userIds);
            }

            return $extension;
        });
    }

    /**
     * Admin inbox: pending extension requests where the given admin is an
     * action-taker on the current workflow step (or the step is open).
     *
     * Mirrors EmployeeTaskRequestService::inbox() for the same access pattern.
     */
    public function listInboxForAdmin(string $adminId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->taskRepo->paginateExtensionInboxForAdmin($adminId, $filters, $perPage);
    }

    public function listInboxAllForAdmin(string $adminId, array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        return $this->taskRepo->allExtensionInboxForAdmin($adminId, $filters);
    }

    /**
     * List all pending extension requests regardless of action-taker.
     *
     * Useful for super-admin views; prefer listInboxForAdmin() for scoped access.
     */
    public function listPending(int $perPage = 15): LengthAwarePaginator
    {
        return EmployeeTaskExtensionRequest::query()
            ->where('status', 'pending')
            ->with([
                'task',
                'requestedByUser',
                'currentProcedureStep.actionTakers.user',
            ])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * List all extension requests for a specific task.
     *
     * Useful for viewing history of extensions on a single task.
     */
    public function listExtensions(string $taskId): Collection
    {
        return $this->listForTask($taskId);
    }

    public function listForTask(string $taskId): Collection
    {
        return EmployeeTaskExtensionRequest::query()
            ->where('employee_task_request_id', $taskId)
            ->with([
                'requestedByUser',
                'reviewedByUser',
            ])
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Retrieve a single extension request with full eager loading.
     *
     * @throws EmployeeTaskException
     */
    public function get(string $extensionId): EmployeeTaskExtensionRequest
    {
        $extension = EmployeeTaskExtensionRequest::query()
            ->with([
                'task',
                'requestedByUser',
                'reviewedByUser',
                'currentProcedureStep.actionTakers.user',
            ])
            ->find($extensionId);

        if (!$extension) {
            throw EmployeeTaskException::extensionNotFound();
        }

        return $extension;
    }

    /**
     * Broadcast task notification to action takers in real-time.
     * Follows the same pattern as ResourceShareService::broadcastToSharedCompany().
     */
    private function broadcastTaskNotification(\Modules\EmployeeTask\Models\EmployeeTaskRequest $task, \Modules\ProcedureSetting\Models\ProcedureSettingStep $currentStep, array $userIds = []): void
    {
        $task->load(['user']);

        if ($userIds === []) {
            $currentStep->load(['actionTakers.user']);
        }

        \Log::info('Broadcasting EmployeeTaskNotification', [
            'task_id'  => $task->id,
            'step_id'  => $currentStep->id,
            'user_ids' => $userIds,
        ]);

        event(new EmployeeTaskNotification($task, $currentStep, $userIds));
    }


    private function resolveExtensionProcedureSetting(\Modules\EmployeeTask\Models\EmployeeTaskRequest $task): ?ProcedureSetting
    {
        $task->loadMissing('user.userProfessionalData');
        $branchId = $task->user?->userProfessionalData?->branch_id;

        return $this->workflow->resolveInternalProcedureSettingByForm(
            ProcedureSettingType::EmployeeTask->value,
            'extendTaskTime',
            $task->company_id,
            $branchId,
        );
    }

    /**
     * Load a specific internal procedure setting by ID, verifying it belongs
     * to the task's company/category parent and has a form set.
     */
    private function loadInternalProcedureSetting(string $id, \Modules\EmployeeTask\Models\EmployeeTaskRequest $task): ?ProcedureSetting
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

        // No steps configured → auto-approve (return null so caller skips workflow)
        if ($setting->steps->isEmpty()) {
            return null;
        }

        return $setting;
    }

    private function dispatchStepNotifications(\Modules\ProcedureSetting\Models\ProcedureSettingStep $step, array $userIds): void
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

        $users = User::query()->whereIn('id', $userIds)->get();
        $notification = new WorkflowActionRequired(null, $step, $channels);

        foreach ($users as $user) {
            try {
                $user->notify($notification);
            } catch (\Throwable $e) {
                \Log::error('WorkflowActionRequired notification failed', [
                    'user_id' => $user->id,
                    'step_id' => $step->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
