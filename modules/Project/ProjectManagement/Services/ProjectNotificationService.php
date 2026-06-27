<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\EmployeeTask\DTO\CreateEmployeeTaskRequestDTO;
use Modules\EmployeeTask\DTO\EndTaskDTO;
use Modules\EmployeeTask\DTO\StartTaskDTO;
use Modules\EmployeeTask\Enums\EmployeeTaskStatus;
use Modules\EmployeeTask\Exceptions\EmployeeTaskException;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\EmployeeTask\Models\EmployeeTaskType;
use Modules\EmployeeTask\Services\EmployeeTaskAvailableActionsService;
use Modules\EmployeeTask\Services\EmployeeTaskLifecycleService;
use Modules\EmployeeTask\Services\EmployeeTaskProceduresService;
use Modules\EmployeeTask\Services\EmployeeTaskRequestService;
use Modules\ProcedureSetting\Events\WorkflowProcedureTaken;
use Modules\ProcedureSetting\Enums\ProcedureSettingType;
use Modules\Process\Enums\ProcessStatus;
use Modules\Process\Enums\ProcessStepStatus;
use Modules\Process\Models\Process;
use Modules\Project\ProjectManagement\DTO\CreateProjectNotificationDTO;
use Modules\Project\ProjectManagement\DTO\FilterProjectNotificationDTO;
use Modules\Project\ProjectManagement\DTO\UpdateProjectNotificationDTO;
use Modules\Project\ProjectManagement\Exceptions\ProjectNotificationException;
use Modules\Project\ProjectManagement\Models\Contractor;
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
        private readonly EmployeeTaskProceduresService $proceduresService,
    ) {}

    public function create(CreateProjectNotificationDTO $dto): ProjectNotification
    {
        $companyId = (string) tenant('id');
        $creator = User::find($dto->createdByUserId);
        $branchId = $creator?->userProfessionalData?->branch_id !== null
            ? (string) $creator->userProfessionalData->branch_id
            : null;

        $data = $this->enrichContractorData($dto->toArray());

        // 1. Create the ProjectNotification row (observer auto-generates notification_number).
        $notification = $this->repository->create([
            ...$data,
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
        // Mobile "My Tasks" tab shows notifications that are approved, started,
        // finished, or rejected.
        $filters['status'] = 'approved,in_progress,completed,rejected';

        return $this->repository->paginatedForMyTasks(
            $filters,
            $userId,
            $dto->perPage ?? 15,
            $dto->sort,
        );
    }

    /**
     * Mobile endpoint: inbox of pending notifications that still need workflow
     * action. Items are selected from the process table where the linked
     * project_notification_task has an in-progress process with a pending step
     * assigned to the current user.
     */
    public function myInbox(FilterProjectNotificationDTO $dto, string $userId): LengthAwarePaginator
    {
        $filters = $dto->toFilters();
        $filters['workflow_inbox_for_user'] = $userId;
        // Inbox holds pending notifications that still need workflow approval.
        $filters['status'] = 'pending';

        return $this->repository->paginated(
            $filters,
            $dto->perPage ?? 15,
            $dto->sort,
        );
    }

    /**
     * Count assigned notifications grouped by status for the mobile inbox badge.
     */
    public function inboxCounts(string $userId, array $filters = []): array
    {
        $query = ProjectNotification::query()
            ->whereIn('project_notifications.status', ['pending']);
        $this->applyWorkflowInboxFilter($query, $userId);

        $this->applyDateFilters($query, $filters);

        $rows = $query
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'pending'  => (int) ($rows['pending'] ?? 0),
        ];
    }

    /**
     * Filter metadata for the mobile filter UI:
     *   - statuses: key, count
     *   - projects: key (project_id), title, count
     *   - duration: min_hours, max_hours
     */
    public function filterMetadata(string $userId, array $filters = []): array
    {
        $base = ProjectNotification::query()
            ->whereIn('project_notifications.status', ['pending']);
        $this->applyWorkflowInboxFilter($base, $userId);

        $this->applyDateFilters($base, $filters);

        $statusQuery = clone $base;
        $statusCounts = $statusQuery
            ->selectRaw('project_notifications.status, COUNT(*) as count')
            ->groupBy('project_notifications.status')
            ->pluck('count', 'project_notifications.status')
            ->toArray();

        $projectQuery = clone $base;
        $projectQuery = $projectQuery->whereNotNull('project_id');
        $projectRows = $projectQuery
            ->leftJoin('projects', 'project_notifications.project_id', '=', 'projects.id')
            ->selectRaw('projects.id as project_id, projects.name as project_name, COUNT(*) as count')
            ->groupBy('projects.id', 'projects.name')
            ->get();

        $projectCounts = [];
        foreach ($projectRows as $row) {
            $projectCounts[] = [
                'id'    => $row->project_id,
                'name'  => $row->project_name,
                'count' => (int) $row->count,
            ];
        }

        $durationQuery = clone $base;
        $durationStats = $durationQuery
            ->selectRaw('MIN(duration_hours) as min_hours, MAX(duration_hours) as max_hours')
            ->first();

        return [
            'status_counts'  => $statusCounts,
            'project_counts' => $projectCounts,
            'duration'       => [
                'min_hours' => $durationStats?->min_hours ? (float) $durationStats->min_hours : null,
                'max_hours' => $durationStats?->max_hours ? (float) $durationStats->max_hours : null,
            ],
        ];
    }

    private function applyDateFilters($query, array $filters): void
    {
        if (!empty($filters['task_date'])) {
            $query->whereDate('task_date', $filters['task_date']);
            return;
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('task_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('task_date', '<=', $filters['date_to']);
        }
    }

    private function applyWorkflowInboxFilter($query, string $userId): void
    {
        $query->whereHas('employeeTask.processes', function ($q) use ($userId) {
            $q->where('processable_type', ProcedureSettingType::ProjectNotificationTask->value)
                ->where('status', ProcessStatus::InProgress)
                ->whereHas('steps', function ($q) use ($userId) {
                    $q->where('status', ProcessStepStatus::Pending)
                        ->where(function ($q) use ($userId) {
                            $q->where('assigned_user_id', $userId)
                                ->orWhereJsonContains('authorized_user_ids', $userId);
                        });
                });
        });
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

        $data = $this->enrichContractorData($dto->toArray());

        $this->repository->update($id, $data);

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
        $task = $notification->employee_task_request_id ? $notification->employeeTask : null;

        // When the linked task is driven by a real approval workflow, advance the
        // workflow step regardless of the notification status. This allows the
        // dashboard to approve subsequent steps (confirm-receive, end, etc.) after
        // the task is already in_progress. The EmployeeTaskStatusSyncObserver
        // mirrors the resulting task status onto the notification once the
        // workflow resolves.
        if ($task && $this->taskHasActiveProcess($task->id)) {
            $this->employeeTaskRequestService->approveWorkflowStep($task->id, $userId);

            $notification->forceFill([
                'approved_by' => $userId,
                'approved_at' => now(),
            ])->save();

            return $notification->fresh();
        }

        if (!in_array($notification->status, ['pending'], true)) {
            throw ProjectNotificationException::cannotApprove($notification->status);
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
        $task = $notification->employee_task_request_id ? $notification->employeeTask : null;

        if ($task && $this->taskHasActiveProcess($task->id)) {
            $this->employeeTaskRequestService->rejectWorkflowStep($task->id, $userId, $reason);

            $notification->forceFill([
                'rejected_by' => $userId,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ])->save();

            return $notification->fresh();
        }

        if (!in_array($notification->status, ['pending'], true)) {
            throw ProjectNotificationException::cannotReject($notification->status);
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

    /**
     * Confirm-receive for project notifications: starts the linked task and moves it
     * from the employee inbox (approved) to the assigned tasks list (in_progress).
     * Internally equivalent to startTask, exposed under the confirm-receive semantics.
     *
     * If the linked task is still pending, it is auto-approved first so the employee
     * can start immediately without a separate dashboard approval step.
     */
    public function confirmReceive(string $notificationId, StartTaskDTO $dto, User $user): EmployeeTaskRequest
    {
        $task = $this->linkedTask($notificationId);

        // If the creation workflow (createProjectNotificationTask) still has pending
        // steps, the employee confirms/approves the current step. On the final step
        // the EmployeeTaskStatusSyncObserver will move the task to approved.
        if ($this->taskHasActiveProcess($task->id)) {
            $this->employeeTaskRequestService->approveWorkflowStep($task->id, (string) $user->id);
            $task = $task->fresh();
        }

        // Legacy fallback: only when the task was created without a workflow and still
        // has no active process, mark it approved directly.
        if (! $this->taskHasActiveProcess($task->id) && $task->status === EmployeeTaskStatus::Pending->value) {
            $task->update([
                'status' => EmployeeTaskStatus::Approved->value,
                'approved_at' => now(),
            ]);
        }

        // Once the creation workflow is complete and the task is approved, start it
        // directly. The create workflow already contained all required steps, so we
        // do not start a separate start-task procedure here.
        if (! $this->taskHasActiveProcess($task->id) && $task->status === EmployeeTaskStatus::Approved->value) {
            if ($task->hasPendingStartRequest()) {
                throw EmployeeTaskException::pendingStartRequestExists();
            }

            $activeTask = EmployeeTaskRequest::query()
                ->where('user_id', $user->id)
                ->whereIn('status', EmployeeTaskStatus::activeStatuses())
                ->first();

            if ($activeTask && $activeTask->id !== $task->id) {
                throw EmployeeTaskException::hasOtherOpenTask();
            }

            $task = $this->lifecycleService->performStart($task, $dto, $user);
        }

        return $task->fresh();
    }

    public function startTask(string $notificationId, StartTaskDTO $dto, User $user): EmployeeTaskRequest
    {
        return $this->confirmReceive($notificationId, $dto, $user);
    }

    public function endTask(string $notificationId, EndTaskDTO $dto): EmployeeTaskRequest
    {
        $task = $this->linkedTask($notificationId);

        return $this->lifecycleService->end($task->id, $dto);
    }

    /**
     * Records a generic internal procedure action (e.g. تحديث) that is returned
     * by availableActions(). Confirm-receive and end are handled by dedicated
     * lifecycle methods; this method is for mid-lifecycle actions such as
     * UpdateProjectNotificationTask. Validates the procedure is currently
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

    /**
     * Return the timeline of taken internal procedures for the linked EmployeeTask.
     * Uses whereHas('projectNotification') to find the EmployeeTaskRequest by the
     * notification id, then queries internal_procedure_takens by the task id.
     *
     * @return array{items: \Illuminate\Database\Eloquent\Collection, summary: array, debug?: array}
     */
    public function procedures(string $notificationId, bool $debug = false): array
    {
        $task = EmployeeTaskRequest::query()
            ->whereHas('projectNotification', function ($query) use ($notificationId) {
                $query->where('id', $notificationId);
            })
            ->first();

        if (! $task) {
            throw ProjectNotificationException::linkedTaskNotFound($notificationId);
        }

        $result = $this->proceduresService->forTask($task->id);

        if ($debug) {
            $result['debug'] = [
                'notification_id'         => $notificationId,
                'task_id'                 => $task->id,
                'is_project_notification' => $task->is_project_notification,
                'processable_type'        => $task->procedureSettingType()->value,
                'processable_id'          => $task->id,
            ];
        }

        return $result;
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

    /**
     * When a contractor_id is provided, auto-fill contractor_name and
     * contractor_number from the contractor record if they are not already
     * supplied by the frontend.
     */
    private function enrichContractorData(array $data): array
    {
        if (empty($data['contractor_id'])) {
            return $data;
        }

        $contractor = Contractor::query()->find($data['contractor_id']);

        if (! $contractor) {
            return $data;
        }

        if (empty($data['contractor_name'])) {
            $data['contractor_name'] = $contractor->name;
        }

        if (empty($data['contractor_number'])) {
            $data['contractor_number'] = $contractor->number;
        }

        return $data;
    }
}
