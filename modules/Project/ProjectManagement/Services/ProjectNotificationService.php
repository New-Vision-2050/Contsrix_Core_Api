<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\EmployeeTask\DTO\CreateEmployeeTaskRequestDTO;
use Modules\EmployeeTask\DTO\EndTaskDTO;
use Modules\EmployeeTask\DTO\StartTaskDTO;
use Modules\EmployeeTask\Enums\EmployeeTaskStatus;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\EmployeeTask\Models\EmployeeTaskType;
use Modules\EmployeeTask\Services\EmployeeTaskAvailableActionsService;
use Modules\EmployeeTask\Services\EmployeeTaskLifecycleService;
use Modules\EmployeeTask\Services\EmployeeTaskRequestService;
use Modules\ProcedureSetting\Events\WorkflowProcedureTaken;
use Modules\ProcedureSetting\Enums\ProcedureSettingType;
use Modules\Process\Enums\ProcessStatus;
use Modules\Process\Models\Process;
use Modules\Project\ProjectManagement\DTO\CreateProjectNotificationDTO;
use Modules\Project\ProjectManagement\DTO\FilterProjectNotificationDTO;
use Modules\Project\ProjectManagement\DTO\UpdateProjectNotificationDTO;
use Modules\Project\ProjectManagement\Exceptions\ProjectNotificationException;
use Modules\Project\ProjectManagement\Models\ProjectNotification;
use Modules\Project\ProjectManagement\Repositories\ProjectNotificationRepository;
use Modules\Shared\InternalProcessType\Enums\InternalProcessForm;
use Modules\User\Models\User;

class ProjectNotificationService
{
    public function __construct(
        private readonly ProjectNotificationRepository $repository,
        private readonly EmployeeTaskRequestService $employeeTaskRequestService,
        private readonly EmployeeTaskLifecycleService $lifecycleService,
        private readonly EmployeeTaskAvailableActionsService $availableActionsService,
    ) {}

    public function create(CreateProjectNotificationDTO $dto): ProjectNotification
    {
        $companyId = (string) tenant('id');
        $creator = User::find($dto->createdByUserId);
        $branchId = $creator?->userProfessionalData?->branch_id !== null
            ? (string) $creator->userProfessionalData->branch_id
            : null;

        // 1. Create the ProjectNotification row (observer auto-generates notification_number).
        $notification = $this->repository->create([
            ...$dto->toArray(),
            'company_id' => $companyId,
            'status' => 'pending',
        ]);

        // 2. Build the linked EmployeeTask DTO.
        $projectNotificationTypeId = $this->resolveProjectNotificationTypeId();

        $taskDto = new CreateEmployeeTaskRequestDTO(
            userId: $dto->assignedUserId,
            title: $notification->notification_number,
            employee_task_type_id: $projectNotificationTypeId,
            itemType: 'project_notification',
            itemId: $notification->id,
            durationHours: $dto->durationHours,
            taskDate: $dto->taskDate,
            taskLatitude: $dto->taskLatitude,
            taskLongitude: $dto->taskLongitude,
            currentLatitude: null,
            currentLongitude: null,
            description: $dto->workDescription,
            projectId: $dto->projectId,
            approvalResponsibleId: $dto->approvalResponsibleId,
            assignmentResponsibleId: $dto->assignmentResponsibleId,
            notes: $dto->notes,
            files: $dto->files,
        );

        // 3. Delegate to EmployeeTaskRequestService with the dedicated form key.
        $task = $this->employeeTaskRequestService->create(
            $taskDto,
            InternalProcessForm::CreateProjectNotificationTask->value,
        );

        // 4. Link the task back to the notification and set dashboard-specific fields.
        $task->update([
            'project_notification_id' => $notification->id,
            'is_project_notification' => true,
            'sender_user_id' => $dto->createdByUserId,
            'task_source' => 'dashboard',
        ]);

        $notification->update(['employee_task_request_id' => $task->id]);

        // 5. Sync notification status from the task.
        $this->syncNotificationStatusFromTask($notification->fresh(), $task);

        return $notification->fresh();
    }

    public function list(FilterProjectNotificationDTO $dto): LengthAwarePaginator
    {
        return $this->repository->paginated(
            $dto->toFilters(),
            $dto->perPage ?? 15,
            $dto->sort,
        );
    }

    /**
     * Mobile endpoint: list project notifications assigned to the current employee,
     * with the same filters as the dashboard list.
     */
    public function myTasks(FilterProjectNotificationDTO $dto, string $userId): LengthAwarePaginator
    {
        $filters = $dto->toFilters();
        $filters['assigned_user_id'] = $userId;

        return $this->repository->paginated(
            $filters,
            $dto->perPage ?? 15,
            $dto->sort,
        );
    }

    public function get(string $id): ProjectNotification
    {
        $notification = $this->repository->findById($id);

        if (!$notification) {
            throw ProjectNotificationException::notFound($id);
        }

        return $notification;
    }

    public function update(string $id, UpdateProjectNotificationDTO $dto): ProjectNotification
    {
        $notification = $this->get($id);

        $this->repository->update($id, $dto->toArray());

        return $notification->fresh();
    }

    public function delete(string $id): bool
    {
        $notification = $this->get($id);

        return $this->repository->delete($id);
    }

    public function approve(string $id, string $userId): ProjectNotification
    {
        $notification = $this->get($id);

        if (!in_array($notification->status, ['pending'], true)) {
            throw ProjectNotificationException::cannotApprove($notification->status);
        }

        $task = $notification->employee_task_request_id ? $notification->employeeTask : null;

        // When the linked task is driven by a real approval workflow, advance the
        // workflow step instead of force-setting the status. The
        // EmployeeTaskStatusSyncObserver mirrors the resulting task status onto
        // the notification once the workflow resolves.
        if ($task && $this->taskHasActiveProcess($task->id)) {
            $this->employeeTaskRequestService->approve($task->id, $userId);

            $notification->forceFill([
                'approved_by' => $userId,
                'approved_at' => now(),
            ])->save();

            return $notification->fresh();
        }

        $notification->update([
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);

        if ($task && $task->status === EmployeeTaskStatus::Pending->value) {
            $task->update([
                'status' => EmployeeTaskStatus::Approved->value,
                'approved_by' => $userId,
                'approved_at' => now(),
            ]);
        }

        return $notification->fresh();
    }

    public function reject(string $id, string $userId, string $reason): ProjectNotification
    {
        $notification = $this->get($id);

        if (!in_array($notification->status, ['pending'], true)) {
            throw ProjectNotificationException::cannotReject($notification->status);
        }

        $task = $notification->employee_task_request_id ? $notification->employeeTask : null;

        if ($task && $this->taskHasActiveProcess($task->id)) {
            $this->employeeTaskRequestService->reject($task->id, $userId, $reason);

            $notification->forceFill([
                'rejected_by' => $userId,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ])->save();

            return $notification->fresh();
        }

        $notification->update([
            'status' => 'rejected',
            'rejected_by' => $userId,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);

        if ($task && $task->status === EmployeeTaskStatus::Pending->value) {
            $task->update([
                'status' => EmployeeTaskStatus::Rejected->value,
                'rejected_by' => $userId,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ]);
        }

        return $notification->fresh();
    }

    private function taskHasActiveProcess(string $taskId): bool
    {
        return Process::query()
            ->where('processable_type', ProcedureSettingType::ProjectNotificationTask->value)
            ->where('processable_id', $taskId)
            ->where('status', ProcessStatus::InProgress)
            ->exists();
    }

    public function syncNotificationStatusFromTask(ProjectNotification $notification, $task): void
    {
        $statusMap = [
            'pending' => 'pending',
            'approved' => 'approved',
            'rejected' => 'rejected',
            'in_progress' => 'in_progress',
            'completed' => 'completed',
            'cancelled' => 'cancelled',
        ];

        $newStatus = $statusMap[$task->status] ?? null;

        if ($newStatus && $notification->status !== $newStatus) {
            $notification->update(['status' => $newStatus]);
        }
    }

    private function resolveProjectNotificationTypeId(): string
    {
        $type = EmployeeTaskType::where('key', 'project_notification')->first();

        if (!$type) {
            throw ProjectNotificationException::taskTypeNotFound();
        }

        return $type->id;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Mobile helpers — delegate to the linked EmployeeTaskRequest
    // ──────────────────────────────────────────────────────────────────────────

    public function availableActions(string $notificationId): array
    {
        $task = $this->linkedTask($notificationId);

        return $this->availableActionsService->forTask($task->id);
    }

    public function startTask(string $notificationId, StartTaskDTO $dto, User $user): EmployeeTaskRequest
    {
        $task = $this->linkedTask($notificationId);

        return $this->lifecycleService->start($task->id, $dto, $user);
    }

    public function endTask(string $notificationId, EndTaskDTO $dto): EmployeeTaskRequest
    {
        $task = $this->linkedTask($notificationId);

        return $this->lifecycleService->end($task->id, $dto);
    }

    /**
     * Records a generic internal procedure action (e.g. تأكيد التواجد or تحديث)
     * that is returned by availableActions(). Validates the procedure is currently
     * available, then fires WorkflowProcedureTaken so downstream actions unlock.
     */
    public function takeAction(
        string $notificationId,
        string $procedureSettingId,
        string $userId,
    ): array {
        $task = $this->linkedTask($notificationId);

        $availableActions = $this->availableActionsService->forTask($task->id);
        $availableIds = array_column($availableActions, 'id');

        if (! in_array($procedureSettingId, $availableIds, true)) {
            throw ProjectNotificationException::procedureNotAvailable();
        }

        event(new WorkflowProcedureTaken(
            $task->procedureSettingType()->value,
            $task->id,
            $procedureSettingId,
            $userId,
        ));

        return ['procedure_setting_id' => $procedureSettingId];
    }

    private function linkedTask(string $notificationId): EmployeeTaskRequest
    {
        $notification = $this->get($notificationId);
        $task = $notification->employeeTask;

        if (! $task) {
            throw ProjectNotificationException::linkedTaskNotFound($notificationId);
        }

        return $task;
    }
}
