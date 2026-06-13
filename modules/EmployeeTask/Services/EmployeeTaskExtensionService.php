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
     * Extension requests inherit the workflow configuration from their parent EmployeeTask.
     * This ensures:
     * - Same approvers who approve tasks also approve extensions
     * - No separate workflow configuration needed
     * - Multi-step approvals work identically
     *
     * Scenarios:
     * 1. Parent task is approved (no workflow): Extension auto-approved immediately
     * 2. Parent task is pending (in workflow): Extension resolves first step of same procedure
     * 3. Parent task was auto-approved (no procedure): Extension auto-approved immediately
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

            // Extension inherits workflow state from parent task
            if ($task->procedure_setting_id === null) {
                // Parent task has no workflow (auto-approved or no procedure configured)
                // Extension also auto-approves
                $data['status'] = 'approved';
                $data['current_procedure_step_id'] = null;
                $data['reviewed_at'] = now();
            } else {
                // Parent task is in workflow
                // Extension enters workflow at the same procedure's first step
                $data['status'] = 'pending';
                $firstStep = $this->workflow->resolveFirstStepBySettingId($task->procedure_setting_id);
                $data['current_procedure_step_id'] = $firstStep->id;
            }

            $extension = EmployeeTaskExtensionRequest::query()->create($data);
            $task->update(['last_extension_status' => 'extension_pending']);

            // Broadcast notification to action takers
            if ($task->procedure_setting_id !== null) {
                $firstStep = $this->workflow->resolveFirstStepBySettingId($task->procedure_setting_id);
                $context   = $task->project_id ? ['project_id' => $task->project_id] : [];
                $userIds   = $this->workflow->resolveActionTakerUserIdsForStep($firstStep, $task->user_id, $context);
                $this->broadcastTaskNotification($task, $firstStep, $userIds);
                $this->requestService->broadcastInboxCounts($userIds);

                // Email + SMS notifications
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
