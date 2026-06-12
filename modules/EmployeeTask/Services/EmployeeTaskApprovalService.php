<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Modules\EmployeeTask\Enums\EmployeeTaskStatus;
use Modules\EmployeeTask\Events\EmployeeTaskNotification;
use Modules\EmployeeTask\Events\InboxCountsUpdated;
use Modules\EmployeeTask\Exceptions\EmployeeTaskException;
use Modules\EmployeeTask\Models\EmployeeTaskApprovalRequest;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\EmployeeTask\Repositories\EmployeeTaskRepository;
use Modules\ProcedureSetting\Notifications\WorkflowActionRequired;
use Modules\ProcedureSetting\Services\ProcedureWorkflowService;
use Modules\Shared\Media\Services\FileUploadService;
use Modules\User\Models\User;

/**
 * Handles the "send for final approval" (ارسال للاعتماد) flow.
 *
 * After a task is approved and started (in_progress or paused),
 * the employee submits a task-completion approval request with optional
 * file attachments. The admin who is the current workflow action-taker
 * then approves/rejects it. Final approval marks the task as approved.
 */
final class EmployeeTaskApprovalService
{
    public function __construct(
        private readonly EmployeeTaskRepository   $taskRepository,
        private readonly ProcedureWorkflowService $workflow,
        private readonly FileUploadService        $fileUploadService,
        private readonly EmployeeTaskRequestService $requestService,
    ) {}

    /**
     * Employee submits a task-completion approval request.
     *
     * Allowed when task is: approved, in_progress, paused, or completed.
     * Only one pending approval request is allowed at a time.
     *
     * @param UploadedFile|UploadedFile[]|null $file
     */
    public function create(
        string $taskId,
        string $userId,
        ?string $notes,
        UploadedFile|array|null $file = null,
    ): EmployeeTaskApprovalRequest {
        $task = $this->taskRepository->findById($taskId);

        if (!$task) {
            throw EmployeeTaskException::notFound();
        }

        $allowedStatuses = [
            EmployeeTaskStatus::Approved->value,
            EmployeeTaskStatus::InProgress->value,
            EmployeeTaskStatus::Paused->value,
            EmployeeTaskStatus::Completed->value,
        ];

        if (!in_array($task->status, $allowedStatuses, true)) {
            throw EmployeeTaskException::approvalRequestNotAllowed();
        }

        if ($task->hasPendingApprovalRequest()) {
            throw EmployeeTaskException::pendingApprovalRequestExists();
        }

        return DB::transaction(function () use ($task, $userId, $notes, $file): EmployeeTaskApprovalRequest {
            $data = [
                'employee_task_request_id' => $task->id,
                'company_id'               => $task->company_id,
                'requested_by'             => $userId,
                'notes'                    => $notes,
            ];

            if ($task->procedure_setting_id === null) {
                $data['status']                    = 'approved';
                $data['current_procedure_step_id'] = null;
                $data['reviewed_at']               = now();

                $approval = EmployeeTaskApprovalRequest::query()->create($data);
                $this->handleFileUpload($approval, $file);

                $task->update(['status' => EmployeeTaskStatus::Approved->value, 'approved_at' => now()]);
                return $approval->load('media');
            }

            $firstStep = $this->workflow->resolveFirstStepBySettingId($task->procedure_setting_id);
            $data['status']                    = 'pending';
            $data['current_procedure_step_id'] = $firstStep->id;

            $approval = EmployeeTaskApprovalRequest::query()->create($data);
            $this->handleFileUpload($approval, $file);

            // Resolve authorized users and broadcast notifications
            $context = $task->project_id ? ['project_id' => $task->project_id] : [];
            $userIds = $this->workflow->resolveActionTakerUserIdsForStep($firstStep, $task->user_id, $context);
            $this->broadcastTaskNotification($task, $firstStep, $userIds);
            $this->requestService->broadcastInboxCounts($userIds);

            // Email + SMS notifications
            $this->dispatchStepNotifications($firstStep, $userIds);

            return $approval->load('media');
        });
    }

    /**
     * Admin approves a task-completion approval request.
     *
     * Advances through the workflow. On final step → marks the task as approved.
     */
    public function approve(string $approvalId, string $adminId, ?string $approvalNotes = null): EmployeeTaskApprovalRequest
    {
        $approval = $this->findOrFail($approvalId);
        $task     = $this->taskRepository->findById($approval->employee_task_request_id);

        if (!$task) {
            throw EmployeeTaskException::notFound();
        }

        if ($approval->status !== 'pending') {
            throw EmployeeTaskException::approvalRequestAlreadyResolved();
        }

        $context = $task->project_id ? ['project_id' => $task->project_id] : [];
        $result = $this->workflow->advance(
            $approval->current_procedure_step_id,
            $task->procedure_setting_id,
            $adminId,
            $task->user_id,
            $context,
        );

        return DB::transaction(function () use ($approval, $task, $result, $adminId, $approvalNotes): EmployeeTaskApprovalRequest {
            if (!$result->isFinal) {
                $approval->update(['current_procedure_step_id' => $result->nextStep->id]);
                return $approval->fresh();
            }

            $approval->update([
                'status'                    => 'approved',
                'reviewed_by'               => $adminId,
                'reviewed_at'               => now(),
                'review_notes'              => $approvalNotes,
                'current_procedure_step_id' => null,
            ]);

            $task->update([
                'status'      => EmployeeTaskStatus::Approved->value,
                'approved_by' => $adminId,
                'approved_at' => now(),
            ]);

            return $approval->fresh();
        });
    }

    /**
     * Admin rejects a task-completion approval request.
     */
    public function reject(string $approvalId, string $adminId, string $rejectionReason): EmployeeTaskApprovalRequest
    {
        $approval = $this->findOrFail($approvalId);
        $task     = $this->taskRepository->findById($approval->employee_task_request_id);

        if (!$task) {
            throw EmployeeTaskException::notFound();
        }

        if ($approval->status !== 'pending') {
            throw EmployeeTaskException::approvalRequestAlreadyResolved();
        }

        $context = $task->project_id ? ['project_id' => $task->project_id] : [];
        $this->workflow->assertCanReject($approval->current_procedure_step_id, $adminId, $task->user_id, $context);

        return DB::transaction(function () use ($approval, $adminId, $rejectionReason): EmployeeTaskApprovalRequest {
            $approval->update([
                'status'                    => 'rejected',
                'reviewed_by'               => $adminId,
                'reviewed_at'               => now(),
                'review_notes'              => $rejectionReason,
                'current_procedure_step_id' => null,
            ]);

            return $approval->fresh();
        });
    }

    public function findOrFail(string $approvalId): EmployeeTaskApprovalRequest
    {
        $approval = EmployeeTaskApprovalRequest::query()
            ->with(['task.user', 'requestedByUser', 'currentProcedureStep.actionTakers.user', 'media'])
            ->find($approvalId);

        if (!$approval) {
            throw EmployeeTaskException::approvalRequestNotFound();
        }

        return $approval;
    }

    // ─── private ─────────────────────────────────────────────────────────────

    /**
     * Upload one or multiple files to the 'attachments' media collection.
     */
    private function handleFileUpload(EmployeeTaskApprovalRequest $approval, UploadedFile|array|null $file): void
    {
        if (empty($file)) {
            return;
        }

        $this->fileUploadService->uploadFile(
            $approval,
            $file,
            'employee-task-approvals/attachments',
            'attachments',
            'public',
        );
    }

    /**
     * Broadcast task notification to action takers in real-time.
     * Follows the same pattern as ResourceShareService::broadcastToSharedCompany().
     */
    private function broadcastTaskNotification(EmployeeTaskRequest $task, \Modules\ProcedureSetting\Models\ProcedureSettingStep $currentStep, array $userIds = []): void
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
