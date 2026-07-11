<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Services;

use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\EmployeeTask\DTO\CreateEmployeeTaskRequestDTO;
use Modules\EmployeeTask\DTO\EndTaskDTO;
use Modules\EmployeeTask\DTO\StartTaskDTO;
use Modules\EmployeeTask\Events\EmployeeTaskNotification;
use Modules\Project\ProjectManagement\Models\ProjectNotificationTaskPostponement;
use Modules\Project\ProjectManagement\Models\ProjectNotificationWorkResumption;
use Modules\Shared\Media\Services\FileUploadService;
use Modules\EmployeeTask\Enums\EmployeeTaskStatus;
use Modules\EmployeeTask\Exceptions\EmployeeTaskException;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\EmployeeTask\Models\EmployeeTaskType;
use Modules\EmployeeTask\Services\EmployeeTaskAvailableActionsService;
use Modules\EmployeeTask\Services\EmployeeTaskFormConditionService;
use Modules\EmployeeTask\Services\EmployeeTaskLifecycleService;
use Modules\EmployeeTask\Services\EmployeeTaskProceduresService;
use Modules\EmployeeTask\Services\EmployeeTaskRequestService;
use Modules\ProcedureSetting\Conditions\ConditionContext;
use Modules\ProcedureSetting\Events\WorkflowProcedureTaken;
use Modules\ProcedureSetting\Enums\ProcedureSettingType;
use Modules\ProcedureSetting\Models\ProcedureSetting;
use Modules\ProcedureSetting\Services\WorkflowEngine;
use Modules\Process\Enums\ProcessStatus;
use Modules\Process\Enums\ProcessStepStatus;
use Modules\Process\Models\Process;
use Modules\Project\ProjectManagement\DTO\CreateProjectNotificationDTO;
use Modules\Project\ProjectManagement\DTO\FilterProjectNotificationDTO;
use Modules\Project\ProjectManagement\DTO\RequestProjectNotificationFineDTO;
use Modules\Project\ProjectManagement\DTO\RequestProjectNotificationLocationConfirmationDTO;
use Modules\Project\ProjectManagement\DTO\RequestProjectNotificationSiteStatusUpdateDTO;
use Modules\Project\ProjectManagement\DTO\RequestProjectNotificationTaskPostponementDTO;
use Modules\Project\ProjectManagement\DTO\RequestProjectNotificationUpdateDTO;
use Modules\Project\ProjectManagement\DTO\RequestProjectNotificationWorkResumptionDTO;
use Modules\Project\ProjectManagement\DTO\RequestProjectNotificationWorkStoppageReportDTO;
use Modules\Project\ProjectManagement\DTO\UpdateProjectNotificationDTO;
use Modules\Project\ProjectManagement\Exceptions\ProjectNotificationException;
use Modules\Project\ProjectManagement\Models\Contractor;
use Modules\Project\ProjectManagement\Models\ProjectNotification;
use Modules\Project\ProjectManagement\Models\ProjectNotificationEndTaskStatus;
use Modules\Project\ProjectManagement\Models\ProjectNotificationRead;
use Modules\Project\ProjectManagement\Models\ProjectNotificationNote;
use Modules\Project\ProjectManagement\Models\ProjectNotificationFine;
use Modules\Project\ProjectManagement\Models\ProjectNotificationFineItem;
use Modules\Project\ProjectManagement\Models\ProjectNotificationLocationConfirmation;
use Modules\Project\ProjectManagement\Models\ProjectNotificationSiteStatus;
use Modules\Project\ProjectManagement\Models\ProjectNotificationSiteStatusUpdate;
use Modules\Project\ProjectManagement\Models\ProjectNotificationType;
use Modules\Project\ProjectManagement\Models\ProjectNotificationUpdateSiteStatus;
use Modules\Project\ProjectManagement\Models\ProjectNotificationWorkStoppageReason;
use Modules\Project\ProjectManagement\Models\ProjectNotificationWorkStoppageReport;
use Modules\Project\ProjectManagement\Models\ProjectNotificationWorkStoppageReportReason;
use Modules\Project\ProjectManagement\Notifications\SiteStatusUpdateRequiredVoiceNotification;
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
        private readonly EmployeeTaskFormConditionService $conditionService,
        private readonly FileUploadService $fileUploadService,
        private readonly WorkflowEngine $engine,
    ) {}

    public function create(CreateProjectNotificationDTO $dto): ProjectNotification
    {
        $companyId = (string) tenant('id');

        // If the user provides a notification_number that already exists, update
        // the existing record instead of creating a duplicate.
        if (! $dto->isDraft && ! empty($dto->notificationNumber)) {
            $existing = ProjectNotification::query()
                ->where('company_id', $companyId)
                ->where('notification_number', $dto->notificationNumber)
                ->first();

            if ($existing) {
                return $this->publishNotification($dto, $existing);
            }
        }

        if ($dto->isDraft) {
            return $this->createDraft($dto);
        }

        return $this->publishNotification($dto);
    }

    /**
     * Prevent lifecycle actions from running against an unpublished draft.
     */
    private function guardNotDraft(ProjectNotification $notification, string $action = 'perform this action'): void
    {
        if ($notification->status === 'draft') {
            throw ProjectNotificationException::validationFailed(
                "Cannot {$action} on a draft notification."
            );
        }
    }

    /**
     * Persist a project notification as a draft. No lifecycle actions are triggered.
     */
    private function createDraft(CreateProjectNotificationDTO $dto): ProjectNotification
    {
        $companyId = (string) tenant('id');

        $data = $this->enrichContractorData($dto->toArray());
        unset($data['files']);

        // If the user sends a notification_number that already exists in this
        // company, update that existing record instead of trying to insert a
        // duplicate. The existing status is preserved so a published record is
        // not accidentally reverted to draft.
        if (! empty($dto->notificationNumber)) {
            $existing = ProjectNotification::query()
                ->where('company_id', $companyId)
                ->where('notification_number', $dto->notificationNumber)
                ->first();

            if ($existing) {
                $this->repository->update($existing->id, [
                    ...$data,
                    'company_id' => $companyId,
                    'status' => $existing->status,
                ]);

                $this->attachFilesToNotification($existing, $dto->files);

                return $this->repository->findById($existing->id) ?? $existing->fresh();
            }
        }

        $notification = $this->repository->create([
            ...$data,
            'company_id' => $companyId,
            'status' => 'draft',
        ]);

        $this->attachFilesToNotification($notification, $dto->files);

        return $this->repository->findById($notification->id) ?? $notification->fresh();
    }

    /**
     * Publish a project notification: create the row and run the full lifecycle
     * (EmployeeTask, workflow processes, notifications, approvals, etc.).
     */
    private function publishNotification(
        CreateProjectNotificationDTO $dto,
        ?ProjectNotification $existing = null,
    ): ProjectNotification {
        $companyId = (string) tenant('id');

        $data = $this->enrichContractorData($dto->toArray());
        unset($data['files']);

        // 1. Create or update the ProjectNotification row.
        if ($existing) {
            $this->repository->update($existing->id, [
                ...$data,
                'company_id' => $companyId,
                'status' => 'pending',
            ]);
            $notification = $existing->fresh();
        } else {
            $notification = $this->repository->create([
                ...$data,
                'company_id' => $companyId,
                'status' => 'pending',
            ]);
        }

        // 2. Build the linked EmployeeTask DTO.
        $projectNotificationTypeId = $this->resolveProjectNotificationTypeId();

        $taskDto = new CreateEmployeeTaskRequestDTO(
            userId: $dto->assignedUserIds[0],
            title: $notification->notification_number,
            employee_task_type_id: $projectNotificationTypeId,
            itemType: 'project_notification',
            itemId: $notification->id,
            durationHours: $dto->durationHours,
            taskDate: $dto->taskDate,
            taskTime: $dto->taskTime,
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
            radiusMeters: $dto->locationRadius,
            independentUserIds: $dto->independentProgress ? $dto->assignedUserIds : null,
        );

        // 3. Create or update the linked EmployeeTask.
        $task = $notification->employeeTask;

        if (! $task) {
            $task = $this->employeeTaskRequestService->create(
                $taskDto,
                InternalProcessForm::CreateProjectNotificationTask->value,
            );

            $task->update([
                'project_notification_id' => $notification->id,
                'is_project_notification' => true,
                'sender_user_id' => $dto->createdByUserId,
                'task_source' => 'dashboard',
            ]);

            $notification->update(['employee_task_request_id' => $task->id]);
        } else {
            $task->update([
                'user_id' => $dto->assignedUserIds[0] ?? $task->user_id,
                'title' => $notification->notification_number,
                'employee_task_type_id' => $projectNotificationTypeId,
                'duration_hours' => $dto->durationHours,
                'task_date' => $dto->taskDate,
                'task_time' => $dto->taskTime,
                'task_latitude' => $dto->taskLatitude,
                'task_longitude' => $dto->taskLongitude,
                'description' => $dto->workDescription,
                'project_id' => $dto->projectId,
                'approval_responsible_id' => $dto->approvalResponsibleId,
                'assignment_responsible_id' => $dto->assignmentResponsibleId,
                'notes' => $dto->notes,
                'radius_meters' => $dto->locationRadius,
                'sender_user_id' => $dto->createdByUserId,
            ]);
        }

        // 5. Inject all assigned users into the workflow process steps so that
        //    any of them can approve/reject when all_users_can_approve is true.
        $this->injectAssignedUsersIntoWorkflow($task, $notification);
        $this->ensureFirstStepAssignedToAssignedUser($task, $notification);
        $this->truncateCreationWorkflowToSingleStep($task);

        // 6. Sync notification status from the task.
        $this->syncNotificationStatusFromTask($notification->fresh(), $task);

        return $this->repository->findById($notification->id) ?? $notification->fresh();
    }

    /**
     * Inject all assigned user IDs into the authorized_user_ids of all pending
     * process steps for the linked task's workflow.
     *
     * When all_users_can_approve is true (default), every assigned user is
     * authorized to approve/reject any workflow step. When false, only the
     * originally resolved action takers remain.
     */
    private function injectAssignedUsersIntoWorkflow(
        EmployeeTaskRequest $task,
        ProjectNotification $notification,
    ): void {
        if (! $notification->all_users_can_approve) {
            return;
        }

        // When independent_progress is true, lifecycle processes are created
        // per-user. Injecting all users into a shared creation process is still
        // valid, but lifecycle methods skip this call entirely.
        $assignedUserIds = $notification->assigned_user_ids ?? [];
        if (empty($assignedUserIds)) {
            return;
        }

        $processes = Process::query()
            ->where('processable_type', ProcedureSettingType::ProjectNotificationTask->value)
            ->where('processable_id', $task->id)
            ->where('status', ProcessStatus::InProgress)
            ->whereHas('steps', fn ($q) => $q->where('status', ProcessStepStatus::Pending))
            ->with('steps')
            ->get();

        foreach ($processes as $process) {
            // Update template_snapshot to include all assigned users.
            $snapshot = $process->template_snapshot ?? [];
            foreach ($snapshot as &$row) {
                $existing = $row['authorized_user_ids'] ?? [$row['assigned_user_id'] ?? null];
                $merged = array_values(array_unique(array_filter(array_merge($existing, $assignedUserIds))));
                $row['authorized_user_ids'] = $merged;
            }
            unset($row);
            $process->update(['template_snapshot' => $snapshot]);

            // Update each pending step's authorized_user_ids.
            foreach ($process->steps as $step) {
                if ($step->status !== ProcessStepStatus::Pending) {
                    continue;
                }
                $existing = $step->authorized_user_ids ?? [$step->assigned_user_id];
                $merged = array_values(array_unique(array_filter(array_merge($existing, $assignedUserIds))));
                $step->update(['authorized_user_ids' => $merged]);
            }
        }
    }

    /**
     * Ensure the shared workflow process for a project notification has its first
     * pending step assigned to the first assigned user (the employee). This makes
     * the task appear in the employee's inbox and triggers a real-time notification
     * for them, regardless of how the procedure settings resolved the action taker.
     */
    private function ensureFirstStepAssignedToAssignedUser(
        EmployeeTaskRequest $task,
        ProjectNotification $notification,
    ): void {
        $assignedUserIds = $notification->assigned_user_ids ?? [];
        if (empty($assignedUserIds)) {
            return;
        }

        $firstUserId = $assignedUserIds[0];

        $processes = Process::query()
            ->where('processable_type', ProcedureSettingType::ProjectNotificationTask->value)
            ->where('processable_id', $task->id)
            ->where('status', ProcessStatus::InProgress)
            ->whereNull('user_id')
            ->whereHas('steps', fn ($q) => $q->where('status', ProcessStepStatus::Pending))
            ->with('steps.procedureSettingStep')
            ->get();

        foreach ($processes as $process) {
            foreach ($process->steps as $step) {
                if ($step->status !== ProcessStepStatus::Pending) {
                    continue;
                }

                $alreadyAssigned = $step->assigned_user_id === $firstUserId;
                $alreadyAuthorized = in_array($firstUserId, $step->authorized_user_ids ?? [], true);

                if (! $alreadyAssigned) {
                    $step->update(['assigned_user_id' => $firstUserId]);
                }

                if (! $alreadyAuthorized) {
                    $authorized = $step->authorized_user_ids ?? [];
                    $authorized[] = $firstUserId;
                    $step->update([
                        'authorized_user_ids' => array_values(array_unique(array_filter($authorized))),
                    ]);
                }

                // Only broadcast when the assignment actually changed; otherwise
                // the WorkflowStepActivated listener already sent the notification.
                if (! $alreadyAssigned && $step->procedureSettingStep !== null) {
                    event(new EmployeeTaskNotification($task, $step->procedureSettingStep, [$firstUserId]));
                }

                break;
            }
        }
    }

    /**
     * Project notification creation should behave as a single confirm-receive
     * step. If the configured procedure setting has additional steps after the
     * first one, truncate the snapshot and remove any already-created pending
     * steps. This prevents the employee from receiving another notification/
     * voice call after confirming receive.
     */
    private function truncateCreationWorkflowToSingleStep(EmployeeTaskRequest $task): void
    {
        $processes = Process::query()
            ->where('processable_type', ProcedureSettingType::ProjectNotificationTask->value)
            ->where('processable_id', $task->id)
            ->where('status', ProcessStatus::InProgress)
            ->with('steps')
            ->get();

        foreach ($processes as $process) {
            $snapshot = $process->template_snapshot ?? [];
            if (count($snapshot) <= 1) {
                continue;
            }

            $firstStepId = $snapshot[0]['step_id'] ?? null;
            $firstRow = $snapshot[0];

            $process->update(['template_snapshot' => [$firstRow]]);

            foreach ($process->steps as $step) {
                if ($step->status !== ProcessStepStatus::Pending) {
                    continue;
                }

                if ($firstStepId !== null && $step->step_id === $firstStepId) {
                    continue;
                }

                $step->delete();
            }
        }
    }

    /**
     * Create a lifecycle workflow process for a project notification, handling
     * the independent_progress flag.
     *
     * When independent_progress is true, the process is scoped to the acting
     * user (independentUserId) so each assigned user progresses through
     * procedures independently. When false, the process is shared and all
     * assigned users are injected into the workflow steps (if all_users_can_approve).
     */
    private function createLifecycleProcessForNotification(
        EmployeeTaskRequest $task,
        ProjectNotification $notification,
        string $formKey,
        array $metadata,
        ?ProcedureSetting $procedureSetting,
        string $userId,
    ): ?Process {
        $independentUserId = $notification->independent_progress ? $userId : null;

        $process = $this->employeeTaskRequestService->createLifecycleProcess(
            $task,
            $formKey,
            $metadata,
            $procedureSetting,
            $independentUserId,
            $notification->company_id,
        );

        // Only inject all users for shared processes (independent_progress = false).
        if (! $notification->independent_progress) {
            $this->injectAssignedUsersIntoWorkflow($task, $notification);
        }

        return $process;
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
     * Map view: all notifications (no pagination) with coordinates, radius, task
     * name, assigned user and branch-timezone receive date.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, ProjectNotification>
     */
    public function mapTasks(FilterProjectNotificationDTO $dto): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repository->allForMap($dto->toFilters());
    }

    /**
     * Mark a notification as read or unread for a specific user.
     */
    public function updateReadStatus(string $notificationId, string $userId, bool $isRead): ProjectNotification
    {
        $notification = $this->get($notificationId);

        if ($isRead) {
            ProjectNotificationRead::updateOrCreate(
                [
                    'project_notification_id' => $notification->id,
                    'user_id' => $userId,
                ],
                ['read_at' => now()]
            );
        } else {
            ProjectNotificationRead::query()
                ->where('project_notification_id', $notification->id)
                ->where('user_id', $userId)
                ->delete();
        }

        return $this->get($notification->id);
    }

    /**
     * Attach an `is_read` boolean attribute to each notification for the given user.
     * This avoids N+1 queries by resolving all read records in a single query.
     */
    public function attachReadStatus(iterable $notifications, string $userId): void
    {
        $items = collect($notifications);

        if ($items->isEmpty()) {
            return;
        }

        $ids = $items->pluck('id')->filter()->unique()->values()->all();

        if (empty($ids)) {
            return;
        }

        $readIds = ProjectNotificationRead::query()
            ->whereIn('project_notification_id', $ids)
            ->where('user_id', $userId)
            ->pluck('project_notification_id')
            ->all();

        foreach ($items as $notification) {
            $notification->setAttribute('is_read', in_array($notification->id, $readIds, true));
        }
    }

    /**
     * List active site statuses for the dropdown in the periodic site status update form.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, ProjectNotificationSiteStatus>
     */
    public function listSiteStatuses(): \Illuminate\Database\Eloquent\Collection
    {
        return ProjectNotificationSiteStatus::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * List active update site statuses for the dropdown in the site status update form.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, ProjectNotificationUpdateSiteStatus>
     */
    public function listUpdateSiteStatuses(): \Illuminate\Database\Eloquent\Collection
    {
        return ProjectNotificationUpdateSiteStatus::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * List active end task statuses for the dropdown in the end task form.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, ProjectNotificationEndTaskStatus>
     */
    public function listEndTaskStatuses(): \Illuminate\Database\Eloquent\Collection
    {
        return ProjectNotificationEndTaskStatus::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Resolve an update site status by UUID or by unique key.
     */
    public function resolveUpdateSiteStatus(string $statusIdOrKey): ProjectNotificationUpdateSiteStatus
    {
        $status = ProjectNotificationUpdateSiteStatus::query()
            ->where(function ($query) use ($statusIdOrKey) {
                $query->where('id', $statusIdOrKey)
                    ->orWhere('key', $statusIdOrKey);
            })
            ->where('is_active', true)
            ->first();

        if (! $status) {
            throw ProjectNotificationException::statusNotFound('Update site');
        }

        return $status;
    }

    /**
     * Resolve an end task status by UUID or by unique key.
     */
    public function resolveEndTaskStatus(string $statusIdOrKey): ProjectNotificationEndTaskStatus
    {
        $status = ProjectNotificationEndTaskStatus::query()
            ->where(function ($query) use ($statusIdOrKey) {
                $query->where('id', $statusIdOrKey)
                    ->orWhere('key', $statusIdOrKey);
            })
            ->where('is_active', true)
            ->first();

        if (! $status) {
            throw ProjectNotificationException::statusNotFound('End task');
        }

        return $status;
    }

    /**
     * Update the site status of a project notification by status UUID or key.
     */
    public function updateSiteStatus(string $notificationId, string $statusIdOrKey): ProjectNotification
    {
        $notification = $this->repository->findById($notificationId);

        if (! $notification) {
            throw ProjectNotificationException::notFound('Project notification not found');
        }

        $status = $this->resolveUpdateSiteStatus($statusIdOrKey);

        $notification->update(['update_site_status_id' => $status->id]);

        // A site status change means the notification has new information for its
        // recipients, so clear any existing read markers so it shows as unread.
        $this->clearReadStatusForNotification($notification->id);

        return $notification->fresh();
    }

    /**
     * Update the end task status of a project notification by status UUID or key.
     */
    public function updateEndTaskStatus(string $notificationId, string $statusIdOrKey): ProjectNotification
    {
        $notification = $this->repository->findById($notificationId);

        if (! $notification) {
            throw ProjectNotificationException::notFound('Project notification not found');
        }

        $status = $this->resolveEndTaskStatus($statusIdOrKey);

        $notification->update(['end_task_status_id' => $status->id]);

        // End task status is a major lifecycle change, so treat the notification
        // as fresh information for all recipients.
        $this->clearReadStatusForNotification($notification->id);

        return $notification->fresh();
    }

    /**
     * Clear all per-user read markers for a notification so it shows as unread.
     */
    private function clearReadStatusForNotification(string $notificationId): void
    {
        ProjectNotificationRead::query()
            ->where('project_notification_id', $notificationId)
            ->delete();
    }

    /**
     * Return all user notes for a notification, newest first, with user/branch/timezone.
     *
     * @return array{items: list<array>, timezone: string}
     */
    public function listNotes(string $notificationId): array
    {
        $notification = $this->get($notificationId);
        $timezone = $notification->employeeTask?->timezone
            ?? getTimeZoneBranchByRequest()
            ?? config('app.timezone');

        $notes = ProjectNotificationNote::query()
            ->where('project_notification_id', $notification->id)
            ->with(['user.userProfessionalData.branch'])
            ->orderByDesc('created_at')
            ->get();

        return [
            'items'    => $notes->map(fn ($note) => $this->formatNote($note, $timezone))->all(),
            'timezone' => $timezone,
        ];
    }

    /**
     * Add a user note to a notification and return the formatted note.
     *
     * @return array<string, mixed>
     */
    public function addNote(string $notificationId, string $userId, string $note): array
    {
        $notification = $this->get($notificationId);
        $timezone = $notification->employeeTask?->timezone
            ?? getTimeZoneBranchByRequest()
            ?? config('app.timezone');

        $noteRecord = ProjectNotificationNote::create([
            'company_id'              => $notification->company_id,
            'project_notification_id' => $notification->id,
            'user_id'                 => $userId,
            'note'                    => $note,
        ]);

        $noteRecord->load(['user.userProfessionalData.branch']);

        return $this->formatNote($noteRecord, $timezone);
    }

    /**
     * Format a single note for API responses.
     */
    private function formatNote(ProjectNotificationNote $note, string $timezone): array
    {
        $user   = $note->user;
        $branch = $user?->userProfessionalData?->branch;

        return [
            'id'         => $note->id,
            'note'       => $note->note,
            'created_at' => $this->formatInTimezone($note->created_at, $timezone),
            'timezone'   => $timezone,
            'user'       => $user ? [
                'id'   => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
            ] : null,
            'branch'     => $branch ? [
                'id'   => $branch->id,
                'name' => $branch->name ?? $branch->name_ar ?? null,
            ] : null,
        ];
    }

    /**
     * List active work stoppage reasons for the dropdown in the work stoppage report form.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, ProjectNotificationWorkStoppageReason>
     */
    public function listWorkStoppageReasons(): \Illuminate\Database\Eloquent\Collection
    {
        return ProjectNotificationWorkStoppageReason::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * List active project notification types from the seeded lookup table.
     * Used to populate the notification type dropdown/filter.
     *
     * @return list<array{id: string, value: string, name_ar: string, name_en: string, sort_order: int, is_active: bool}>
     */
    public function listNotificationTypes(): array
    {
        return ProjectNotificationType::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($type) => [
                'id' => $type->id,
                'value' => $type->name_ar,
                'name_ar' => $type->name_ar,
                'name_en' => $type->name_en,
                'sort_order' => $type->sort_order,
                'is_active' => $type->is_active,
            ])
            ->values()
            ->all();
    }

    /**
     * Mobile endpoint: list project notifications assigned to the current employee,
     * with the same filters as the dashboard list.
     */
    public function myTasks(FilterProjectNotificationDTO $dto, string $userId): LengthAwarePaginator
    {
        $filters = $dto->toFilters();
        // Mobile "My Tasks" tab shows notifications that are approved, started,
        // finished, or rejected. "received" and "confirmed_location" are both
        // included since they represent the two in-progress sub-states (location
        // not yet confirmed / confirmed).
        $filters['status'] = 'received,confirmed_location,completed';

        return $this->repository->paginatedForMyTasks(
            $filters,
            $userId,
            $dto->perPage ?? 15,
            $dto->sort,
        );
    }

    /**
     * Mobile endpoint: inbox of notifications that still need workflow action.
     * Items are selected from the process table where the linked
     * project_notification_task has an in-progress process with a pending step
     * assigned to the current user. No status filter is applied so updates on
     * approved/in-progress tasks also appear.
     */
    public function myInbox(FilterProjectNotificationDTO $dto, string $userId): LengthAwarePaginator
    {
        return $this->repository->paginatedForInbox(
            $dto->toFilters(),
            $userId,
            $dto->perPage ?? 15,
            $dto->sort,
        );
    }

    /**
     * Count notifications that have a pending workflow process assigned to the
     * user, regardless of the top-level notification status.
     */
    public function inboxCounts(string $userId, array $filters = []): array
    {
        $query = ProjectNotification::query()
            ->where('project_notifications.status', '!=', 'draft');
        $this->applyWorkflowInboxFilter($query, $userId);

        $this->applyDateFilters($query, $filters);

        $rows = $query
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'pending' => (int) array_sum($rows),
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
            ->where('project_notifications.status', '!=', 'draft');
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
        $query->whereHas(
            'employeeTask.processes',
            $this->engine->pendingProcessScopeForUser(
                ProcedureSettingType::ProjectNotificationTask->value,
                $userId,
            ),
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

        if ($notification->status === 'draft') {
            return $dto->isDraft
                ? $this->updateDraft($notification, $dto)
                : $this->publishDraft($notification, $dto);
        }

        if ($dto->isDraft) {
            throw ProjectNotificationException::validationFailed('Cannot revert a published notification to draft.');
        }

        return $this->updatePublished($notification, $dto);
    }

    /**
     * Overwrite a draft notification with the supplied data. Lifecycle actions are
     * skipped and the status remains 'draft'.
     */
    private function updateDraft(
        ProjectNotification $notification,
        UpdateProjectNotificationDTO $dto,
    ): ProjectNotification {
        $data = $this->enrichContractorData($dto->toDraftArray());
        unset($data['files'], $data['deleted_media_ids']);

        $this->repository->update($notification->id, $data);

        $this->syncNotificationMedia($notification, $dto->deletedMediaIds, $dto->files);

        return $this->get($notification->id);
    }

    /**
     * Publish an existing draft notification by finalizing the row and running
     * the full lifecycle.
     */
    private function publishDraft(
        ProjectNotification $notification,
        UpdateProjectNotificationDTO $dto,
    ): ProjectNotification {
        $data = $this->enrichContractorData($dto->toArray());
        unset($data['files'], $data['deleted_media_ids']);

        $publishedData = [
            ...$this->fillPublishDataFromDraft($notification, $data),
            'status' => 'pending',
        ];

        $this->repository->update($notification->id, $publishedData);

        $notification = $notification->fresh();

        if (! empty($dto->deletedMediaIds)) {
            foreach ($dto->deletedMediaIds as $mediaId) {
                $media = $notification->getMedia('attachments')->where('id', $mediaId)->first();
                $media?->delete();
            }
        }

        $projectNotificationTypeId = $this->resolveProjectNotificationTypeId();

        $assignedUserIds = $notification->assigned_user_ids ?? [];

        $taskDto = new CreateEmployeeTaskRequestDTO(
            userId: (string) ($assignedUserIds[0] ?? ''),
            title: $notification->notification_number,
            employee_task_type_id: $projectNotificationTypeId,
            itemType: 'project_notification',
            itemId: $notification->id,
            durationHours: (float) $notification->duration_hours,
            taskDate: $notification->task_date?->format('Y-m-d') ?? '',
            taskTime: $notification->task_time?->format('H:i'),
            taskLatitude: (float) $notification->task_latitude,
            taskLongitude: (float) $notification->task_longitude,
            currentLatitude: null,
            currentLongitude: null,
            description: $notification->work_description,
            projectId: $notification->project_id,
            approvalResponsibleId: $dto->approvalResponsibleId,
            assignmentResponsibleId: $dto->assignmentResponsibleId,
            notes: $notification->notes,
            files: $dto->files,
            radiusMeters: $notification->location_radius,
            independentUserIds: $notification->independent_progress ? $assignedUserIds : null,
        );

        $task = $this->employeeTaskRequestService->create(
            $taskDto,
            InternalProcessForm::CreateProjectNotificationTask->value,
        );

        $task->update([
            'project_notification_id' => $notification->id,
            'is_project_notification' => true,
            'sender_user_id' => $notification->created_by_user_id,
            'task_source' => 'dashboard',
        ]);

        $notification->update(['employee_task_request_id' => $task->id]);

        $this->injectAssignedUsersIntoWorkflow($task, $notification);
        $this->ensureFirstStepAssignedToAssignedUser($task, $notification);
        $this->truncateCreationWorkflowToSingleStep($task);
        $this->syncNotificationStatusFromTask($notification->fresh(), $task);

        return $this->get($notification->id);
    }

    /**
     * Merge the draft's existing data with the final publish payload so required
     * fields already saved on the draft are not lost.
     */
    private function fillPublishDataFromDraft(ProjectNotification $notification, array $data): array
    {
        $requiredFields = [
            'project_id',
            'assigned_user_ids',
            'task_date',
            'duration_hours',
            'task_latitude',
            'task_longitude',
        ];

        foreach ($requiredFields as $field) {
            if (! isset($data[$field]) || $data[$field] === null || $data[$field] === '') {
                $existing = $notification->getAttribute($field);
                if ($existing !== null && $existing !== '') {
                    $data[$field] = $existing;
                }
            }
        }

        foreach ($requiredFields as $field) {
            if (! isset($data[$field]) || $data[$field] === null || $data[$field] === '') {
                throw ProjectNotificationException::validationFailed(
                    "Missing required field for publishing: {$field}"
                );
            }
        }

        if (! is_array($data['assigned_user_ids']) || empty($data['assigned_user_ids'])) {
            throw ProjectNotificationException::validationFailed(
                'Missing required field for publishing: assigned_user_ids'
            );
        }

        return $data;
    }

    /**
     * Standard update for an already-published notification.
     */
    private function updatePublished(
        ProjectNotification $notification,
        UpdateProjectNotificationDTO $dto,
    ): ProjectNotification {
        $data = $this->enrichContractorData($dto->toArray());

        // Append new assigned users instead of replacing the existing list.
        $newlyAssignedUserIds = [];
        if (! empty($dto->assignedUserIds)) {
            $existingUserIds = $notification->assigned_user_ids ?? [];
            $mergedUserIds = array_values(array_unique(array_filter(array_merge($existingUserIds, $dto->assignedUserIds))));
            $newlyAssignedUserIds = array_values(array_diff($mergedUserIds, $existingUserIds));
            $data['assigned_user_ids'] = $mergedUserIds;
        }

        $this->repository->update($notification->id, $data);

        $this->syncNotificationMedia($notification, $dto->deletedMediaIds, $dto->files);

        // When new employees are appended, make sure they can take action:
        // - independent_progress=true  → each new user gets their own workflow process.
        // - all_users_can_approve=true → new users are injected into pending shared steps.
        if (! empty($newlyAssignedUserIds)) {
            $notification = $notification->fresh();
            $task = $notification->employeeTask;
            if ($task !== null) {
                if ($notification->independent_progress) {
                    $this->employeeTaskRequestService->createIndependentProcessesForUsers(
                        $task,
                        $newlyAssignedUserIds,
                        InternalProcessForm::CreateProjectNotificationTask->value,
                    );
                }

                if ($notification->all_users_can_approve) {
                    $this->injectAssignedUsersIntoWorkflow($task, $notification);
                }
            }
        }

        return $this->get($notification->id);
    }

    /**
     * Apply media deletions and uploads for a notification.
     *
     * @param array<int, int>|null $deletedMediaIds
     * @param array<int, \Illuminate\Http\UploadedFile>|null $files
     */
    private function syncNotificationMedia(
        ProjectNotification $notification,
        ?array $deletedMediaIds,
        ?array $files,
    ): void {
        if (! empty($deletedMediaIds)) {
            foreach ($deletedMediaIds as $mediaId) {
                $media = $notification->getMedia('attachments')->where('id', $mediaId)->first();
                $media?->delete();
            }
        }

        $this->attachFilesToNotification($notification, $files);
    }

    /**
     * Request a workflow-based update of project notification data.
     * Creates a Process snapshot with the new data; the actual DB update is
     * applied only when the process completes (all steps approved).
     */
    public function requestUpdate(
        string $id,
        RequestProjectNotificationUpdateDTO $dto,
        string $userId,
    ): ProjectNotification {
        $notification = $this->get($id);
        $this->guardNotDraft($notification, 'request update');
        $task = $this->linkedTask($id);

        $procedureSetting = $this->engine->resolveLifecycleSetting(
            $dto->internalProcedureSettingId,
            $task->procedureSettingType()->value,
            InternalProcessForm::UpdateProjectNotificationTask->value,
            $task->company_id,
            $task->user?->userProfessionalData?->branch_id !== null
                ? (string) $task->user->userProfessionalData->branch_id
                : null,
        );

        if ($procedureSetting === null) {
            // No procedure configured → apply immediately.
            $this->repository->update($id, $this->enrichContractorData($dto->toArray()));
            $this->attachUpdateFiles($notification, $dto->files);

            // Record the taken procedure with form data metadata.
            event(new WorkflowProcedureTaken(
                processableType:    $task->procedureSettingType()->value,
                processableId:      $task->id,
                procedureSettingId: $dto->internalProcedureSettingId,
                takenBy:            $userId,
                metadata:           ['update' => $dto->toArray()],
                userId:             $notification->independent_progress ? $userId : null,
            ));

            return $this->get($id);
        }

        $metadata = [
            'form'   => InternalProcessForm::UpdateProjectNotificationTask->value,
            'update' => $dto->toArray(),
            'files'  => $this->stageUpdateFiles($notification, $dto->files),
        ];

        $this->createLifecycleProcessForNotification(
            $task,
            $notification,
            InternalProcessForm::UpdateProjectNotificationTask->value,
            $metadata,
            $procedureSetting,
            $userId,
        );

        return $this->get($id);
    }

    /**
     * Request a workflow-based periodic site status update.
     * Creates a Process snapshot with the new data; the actual site status update
     * record is created only when the process completes (all steps approved).
     */
    public function requestSiteStatusUpdate(
        string $id,
        RequestProjectNotificationSiteStatusUpdateDTO $dto,
        string $userId,
    ): ProjectNotification {
        $notification = $this->get($id);
        $task = $this->linkedTask($id);

        $this->checkWorkflowFormConditions(
            InternalProcessForm::UpdateProjectNotificationSiteStatus->value,
            $task,
            $notification,
            $dto->currentLatitude,
            $dto->currentLongitude,
        );

        $updateSiteStatusId = $dto->statusId
            ? $this->resolveUpdateSiteStatus($dto->statusId)->id
            : null;

        $procedureSetting = $this->engine->resolveLifecycleSetting(
            $dto->internalProcedureSettingId,
            $task->procedureSettingType()->value,
            InternalProcessForm::UpdateProjectNotificationSiteStatus->value,
            $task->company_id,
            $task->user?->userProfessionalData?->branch_id !== null
                ? (string) $task->user->userProfessionalData->branch_id
                : null,
        );

        if ($procedureSetting === null) {
            // No procedure configured → create immediately and update the chosen status.
            $this->createSiteStatusUpdateRecord($notification, $task, $dto, $userId);

            if ($updateSiteStatusId) {
                $notification->update(['update_site_status_id' => $updateSiteStatusId]);
            }

            return $this->get($id);
        }

        $metadata = [
            'form' => InternalProcessForm::UpdateProjectNotificationSiteStatus->value,
            'update' => $dto->toArray(),
            'update_site_status_id' => $updateSiteStatusId,
            'files' => $this->stageSiteStatusUpdateFiles($notification, $dto->files),
            'user_id' => $userId,
        ];

        $this->createLifecycleProcessForNotification(
            $task,
            $notification,
            InternalProcessForm::UpdateProjectNotificationSiteStatus->value,
            $metadata,
            $procedureSetting,
            $userId,
        );

        return $this->get($id);
    }

    /**
     * Send a voice call reminder to the assigned user asking them to update
     * the site status for the given project notification.
     */
    public function notifySiteStatusUpdateByVoice(string $id): ProjectNotification
    {
        $notification = $this->get($id);
        $user = $notification->assigned_user;

        if (! $user || trim((string) $user->phone) === '') {
            throw ProjectNotificationException::voiceRecipientHasNoPhone();
        }

        $user->notify(new SiteStatusUpdateRequiredVoiceNotification($notification));

        return $notification;
    }

    /**
     * Request a workflow-based fine (penalty) record for a project notification.
     * Creates a Process snapshot with the fine data; the actual fine record is
     * created only when the process completes (all steps approved).
     */
    public function requestFine(
        string $id,
        RequestProjectNotificationFineDTO $dto,
        string $userId,
    ): ProjectNotification {
        $notification = $this->get($id);
        $this->guardNotDraft($notification, 'request fine');
        $task = $this->linkedTask($id);

        $this->checkWorkflowFormConditions(
            InternalProcessForm::ProjectNotificationFine->value,
            $task,
            $notification,
            $dto->currentLatitude,
            $dto->currentLongitude,
        );

        $procedureSetting = $this->engine->resolveLifecycleSetting(
            $dto->internalProcedureSettingId,
            $task->procedureSettingType()->value,
            InternalProcessForm::ProjectNotificationFine->value,
            $task->company_id,
            $task->user?->userProfessionalData?->branch_id !== null
                ? (string) $task->user->userProfessionalData->branch_id
                : null,
        );

        if ($procedureSetting === null) {
            // No procedure configured → create immediately.
            $this->createFineRecord($notification, $task, $dto, $userId);

            return $this->get($id);
        }

        $metadata = [
            'form' => InternalProcessForm::ProjectNotificationFine->value,
            'update' => [
                'reason' => $dto->reason,
                'items' => $dto->items,
                'total_amount' => $dto->totalAmount(),
            ],
            'files' => $this->stageFineFiles($notification, $dto->files),
            'user_id' => $userId,
        ];

        $this->createLifecycleProcessForNotification(
            $task,
            $notification,
            InternalProcessForm::ProjectNotificationFine->value,
            $metadata,
            $procedureSetting,
            $userId,
        );

        return $this->get($id);
    }

    /**
     * Request a workflow-based location confirmation for a project notification.
     * Creates a Process snapshot with the location data; the actual location
     * confirmation record is created only when the process completes.
     */
    public function requestLocationConfirmation(
        string $id,
        RequestProjectNotificationLocationConfirmationDTO $dto,
        string $userId,
    ): ProjectNotification {
        $notification = $this->get($id);
        $task = $this->linkedTask($id);

        $this->checkWorkflowFormConditions(
            InternalProcessForm::ConfirmProjectNotificationLocation->value,
            $task,
            $notification,
            $dto->latitude,
            $dto->longitude,
        );

        $procedureSetting = $this->engine->resolveLifecycleSetting(
            $dto->internalProcedureSettingId,
            $task->procedureSettingType()->value,
            InternalProcessForm::ConfirmProjectNotificationLocation->value,
            $task->company_id,
            $task->user?->userProfessionalData?->branch_id !== null
                ? (string) $task->user->userProfessionalData->branch_id
                : null,
        );

        if ($procedureSetting === null) {
            // No procedure configured → create immediately.
            $this->createLocationConfirmationRecord($notification, $task, $dto, $userId);

            return $this->get($id);
        }

        $metadata = [
            'form' => InternalProcessForm::ConfirmProjectNotificationLocation->value,
            'update' => $dto->toArray(),
            'user_id' => $userId,
        ];

        $this->createLifecycleProcessForNotification(
            $task,
            $notification,
            InternalProcessForm::ConfirmProjectNotificationLocation->value,
            $metadata,
            $procedureSetting,
            $userId,
        );

        return $this->get($id);
    }

    /**
     * Request a workflow-based work stoppage report for a project notification.
     * Creates a Process snapshot with the report data; the actual report record is
     * created only when the process completes (all steps approved).
     */
    public function requestWorkStoppageReport(
        string $id,
        RequestProjectNotificationWorkStoppageReportDTO $dto,
        string $userId,
    ): ProjectNotification {
        $notification = $this->get($id);
        $task = $this->linkedTask($id);

        $procedureSetting = $this->engine->resolveLifecycleSetting(
            $dto->internalProcedureSettingId,
            $task->procedureSettingType()->value,
            InternalProcessForm::ProjectNotificationWorkStoppageReport->value,
            $task->company_id,
            $task->user?->userProfessionalData?->branch_id !== null
                ? (string) $task->user->userProfessionalData->branch_id
                : null,
        );

        if ($procedureSetting === null) {
            // No procedure configured → create immediately.
            $this->createWorkStoppageReportRecord($notification, $task, $dto, $userId);

            return $this->get($id);
        }

        $metadata = [
            'form' => InternalProcessForm::ProjectNotificationWorkStoppageReport->value,
            'update' => [
                'other_notes' => $dto->otherNotes,
                'reasons' => $dto->reasons,
            ],
            'files' => $this->stageWorkStoppageReportFiles($notification, $dto->files),
            'user_id' => $userId,
        ];

        $this->createLifecycleProcessForNotification(
            $task,
            $notification,
            InternalProcessForm::ProjectNotificationWorkStoppageReport->value,
            $metadata,
            $procedureSetting,
            $userId,
        );

        return $this->get($id);
    }

    /**
     * Request a workflow-based work resumption for a project notification.
     * Creates a Process snapshot with the resumption data; the actual record is
     * created only when the process completes (all steps approved).
     */
    public function requestWorkResumption(
        string $id,
        RequestProjectNotificationWorkResumptionDTO $dto,
        string $userId,
    ): ProjectNotification {
        $notification = $this->get($id);
        $task = $this->linkedTask($id);

        $procedureSetting = $this->engine->resolveLifecycleSetting(
            $dto->internalProcedureSettingId,
            $task->procedureSettingType()->value,
            InternalProcessForm::ProjectNotificationWorkResumption->value,
            $task->company_id,
            $task->user?->userProfessionalData?->branch_id !== null
                ? (string) $task->user->userProfessionalData->branch_id
                : null,
        );

        if ($procedureSetting === null) {
            $this->createWorkResumptionRecord($notification, $task, $dto, $userId);

            return $this->get($id);
        }

        $metadata = [
            'form' => InternalProcessForm::ProjectNotificationWorkResumption->value,
            'update' => [
                'reasons_resolved' => $dto->reasonsResolved,
                'safety_notes_reviewed' => $dto->safetyNotesReviewed,
                'site_ready' => $dto->siteReady,
                'contractor_notified' => $dto->contractorNotified,
                'notes' => $dto->notes,
            ],
            'files' => $this->stageWorkResumptionFiles($notification, $dto->files),
            'user_id' => $userId,
        ];

        $this->createLifecycleProcessForNotification(
            $task,
            $notification,
            InternalProcessForm::ProjectNotificationWorkResumption->value,
            $metadata,
            $procedureSetting,
            $userId,
        );

        return $this->get($id);
    }

    /**
     * Request a workflow-based task postponement for a project notification.
     * On approval, the linked task's date and time are updated to the new values.
     */
    public function requestTaskPostponement(
        string $id,
        RequestProjectNotificationTaskPostponementDTO $dto,
        string $userId,
    ): ProjectNotification {
        $notification = $this->get($id);
        $task = $this->linkedTask($id);

        $procedureSetting = $this->engine->resolveLifecycleSetting(
            $dto->internalProcedureSettingId,
            $task->procedureSettingType()->value,
            InternalProcessForm::ProjectNotificationTaskPostponement->value,
            $task->company_id,
            $task->user?->userProfessionalData?->branch_id !== null
                ? (string) $task->user->userProfessionalData->branch_id
                : null,
        );

        if ($procedureSetting === null) {
            $this->applyTaskPostponement(
                $notification,
                $task,
                $dto->newTaskDate,
                $dto->newTaskTime,
                $userId,
            );

            return $this->get($id);
        }

        $metadata = [
            'form' => InternalProcessForm::ProjectNotificationTaskPostponement->value,
            'update' => [
                'new_task_date' => $dto->newTaskDate,
                'new_task_time' => $dto->newTaskTime,
                'reason' => $dto->reason,
            ],
            'user_id' => $userId,
        ];

        $this->createLifecycleProcessForNotification(
            $task,
            $notification,
            InternalProcessForm::ProjectNotificationTaskPostponement->value,
            $metadata,
            $procedureSetting,
            $userId,
        );

        return $this->get($id);
    }

    public function delete(string $id): bool
    {
        $notification = $this->get($id);

        return $this->repository->delete($id);
    }

    public function approve(string $id, string $userId, ?string $procedureSettingId = null): ProjectNotification
    {
        $notification = $this->get($id);
        $this->guardNotDraft($notification, 'approve');
        $task = $notification->employee_task_request_id ? $notification->employeeTask : null;

        // When the linked task is driven by a real approval workflow, advance the
        // workflow step regardless of the notification status. This allows the
        // dashboard to approve subsequent steps (confirm-receive, end, etc.) after
        // the task is already in_progress. The EmployeeTaskStatusSyncObserver
        // mirrors the resulting task status onto the notification once the
        // workflow resolves.
        if ($task && $this->taskHasActiveProcess($task->id)) {
            // Resolve the correct procedure_setting_id: if the provided one matches
            // an active process, use it; otherwise fall back to the first active
            // process so the approve always targets a real pending workflow.
            $resolvedProcedureSettingId = $this->resolveProcedureSettingIdForActiveProcess(
                $task->id,
                $procedureSettingId,
            );

            $this->employeeTaskRequestService->approveWorkflowStep($task->id, $userId, $resolvedProcedureSettingId);

            $notification->forceFill([
                'approved_by' => $userId,
                'approved_at' => now(),
            ])->save();

            return $this->get($id);
        }

        if (!in_array($notification->status, ['pending', 'in_progress'], true)) {
            throw ProjectNotificationException::cannotApprove($notification->status);
        }

        if ($notification->status === 'pending') {
            $notification->update([
                'status' => 'in_progress',
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
        } else {
            $notification->forceFill([
                'approved_by' => $userId,
                'approved_at' => now(),
            ])->save();
        }

        return $this->get($id);
    }

    public function reject(string $id, string $userId, string $reason, ?string $procedureSettingId = null): ProjectNotification
    {
        $notification = $this->get($id);
        $this->guardNotDraft($notification, 'reject');
        $task = $notification->employee_task_request_id ? $notification->employeeTask : null;

        if ($task && $this->taskHasActiveProcess($task->id)) {
            $resolvedProcedureSettingId = $this->resolveProcedureSettingIdForActiveProcess(
                $task->id,
                $procedureSettingId,
            );

            $this->employeeTaskRequestService->rejectWorkflowStep($task->id, $userId, $reason, $resolvedProcedureSettingId);

            $notification->forceFill([
                'rejected_by' => $userId,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ])->save();

            return $this->get($id);
        }

        if (!in_array($notification->status, ['pending', 'in_progress'], true)) {
            throw ProjectNotificationException::cannotReject($notification->status);
        }

        if ($notification->status === 'pending') {
            $notification->update([
                'status' => 'cancelled',
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
        } else {
            $notification->forceFill([
                'rejected_by' => $userId,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ])->save();
        }

        return $this->get($id);
    }

    /**
     * Resolve the in-progress processes that have a pending step assigned to the
     * given user. Used by the mobile inbox to show which workflow(s) need action.
     *
     * @return list<array{process_id: string, procedure_setting_id: string, form: string, form_label: ?string, mobile_inbox_action_key: string, pending_step_id: string, pending_step_order: int}>
     */
    public function resolvePendingProcessesForInbox(ProjectNotification $notification, string $userId): array
    {
        $task = $notification->employeeTask;

        if ($task === null) {
            return [];
        }

        return $this->engine->resolvePendingProcessesForUser($task, $userId);
    }

    private function taskHasActiveProcess(string $taskId, ?string $procedureSettingId = null): bool
    {
        return $this->engine->hasActiveProcess(
            ProcedureSettingType::ProjectNotificationTask->value,
            $taskId,
            $procedureSettingId,
        );
    }

    private function hasPendingProcessForUser(EmployeeTaskRequest $task, string $userId): bool
    {
        return Process::query()
            ->where('processable_type', ProcedureSettingType::ProjectNotificationTask->value)
            ->where('processable_id', $task->id)
            ->where('user_id', $userId)
            ->where('status', \Modules\Process\Enums\ProcessStatus::InProgress)
            ->whereHas('steps', fn ($q) => $q
                ->where('status', \Modules\Process\Enums\ProcessStepStatus::Pending)
                ->where('assigned_user_id', $userId)
            )
            ->exists();
    }

    /**
     * Cancel ALL old in-progress processes for the task so neither the previous
     * assignees nor the new assignees have stale processes (confirm-receive,
     * end-task, update, site-status, fines, etc.) that would cause duplicate
     * inbox entries or orphaned workflows after reassignment.
     */
    private function cancelOldConfirmReceiveProcesses(EmployeeTaskRequest $task): void
    {
        $oldProcesses = Process::query()
            ->where('processable_type', ProcedureSettingType::ProjectNotificationTask->value)
            ->where('processable_id', $task->id)
            ->where('status', \Modules\Process\Enums\ProcessStatus::InProgress)
            ->get();

        foreach ($oldProcesses as $oldProcess) {
            $oldProcess->steps()
                ->where('status', \Modules\Process\Enums\ProcessStepStatus::Pending)
                ->update(['status' => \Modules\Process\Enums\ProcessStepStatus::Rejected]);

            $oldProcess->update(['status' => \Modules\Process\Enums\ProcessStatus::Completed]);
        }
    }

    /**
     * Resolve the procedure_setting_id to use for workflow step approval.
     *
     * If the provided ID matches an active process, use it directly.
     * Otherwise, fall back to the first active process's procedure_setting_id
     * so the approve/reject always targets a real pending workflow.
     */
    private function resolveProcedureSettingIdForActiveProcess(string $taskId, ?string $procedureSettingId): ?string
    {
        if ($procedureSettingId !== null && $this->taskHasActiveProcess($taskId, $procedureSettingId)) {
            return $procedureSettingId;
        }

        // Fall back to the first active process.
        $process = \Modules\Process\Models\Process::query()
            ->where('processable_type', ProcedureSettingType::ProjectNotificationTask->value)
            ->where('processable_id', $taskId)
            ->where('status', \Modules\Process\Enums\ProcessStatus::InProgress)
            ->first();

        return $process?->procedure_setting_id;
    }

    /**
     * Apply a task postponement by updating both the notification and the linked
     * employee task with the new date and time. Also stores a historical record.
     */
    public function applyTaskPostponement(
        ProjectNotification $notification,
        EmployeeTaskRequest $task,
        string $newTaskDate,
        string $newTaskTime,
        string $userId,
        ?string $processId = null,
        ?string $procedureSettingId = null,
        ?string $reason = null,
    ): ProjectNotificationTaskPostponement {
        $postponement = ProjectNotificationTaskPostponement::query()->create([
            'company_id' => $notification->company_id,
            'project_notification_id' => $notification->id,
            'employee_task_request_id' => $task->id,
            'process_id' => $processId,
            'procedure_setting_id' => $procedureSettingId,
            'previous_task_date' => $notification->task_date,
            'previous_task_time' => $notification->task_time,
            'new_task_date' => $newTaskDate,
            'new_task_time' => $newTaskTime,
            'reason' => $reason,
            'status' => 'approved',
            'requested_by' => $userId,
        ]);

        $notification->update([
            'task_date' => $newTaskDate,
            'task_time' => $newTaskTime,
        ]);

        $task->update([
            'task_date' => $newTaskDate,
            'task_time' => $newTaskTime,
        ]);

        return $postponement;
    }

    public function syncNotificationStatusFromTask(ProjectNotification $notification, $task): void
    {
        $statusMap = [
            'pending' => 'pending',
            'approved' => 'in_progress',
            'rejected' => 'cancelled',
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

    public function availableActions(string $notificationId, ?string $userId = null): array
    {
        $task = $this->linkedTask($notificationId);

        $notification = $this->get($notificationId);
        $scopedUserId = $notification->independent_progress ? $userId : null;

        return $this->availableActionsService->forTask($task->id, $scopedUserId);
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
        $notification = $this->get($notificationId);
        $this->guardNotDraft($notification, 'confirm receive');
        $task = $notification->employeeTask;

        if (! $task) {
            throw ProjectNotificationException::linkedTaskNotFound($notificationId);
        }

        // If the creation workflow (createProjectNotificationTask) still has pending
        // steps, the employee confirms/approves the current step. On the final step
        // the EmployeeTaskStatusSyncObserver will move the task to approved.
        if ($this->taskHasActiveProcess($task->id)) {
            $this->employeeTaskRequestService->approveWorkflowStep($task->id, (string) $user->id);
            $task = $task->fresh();

            // If the pre-created confirm-receive process was approved and the listener
            // started the task, record the receipt timestamp for the new lifecycle.
            if ($task->status === EmployeeTaskStatus::InProgress->value && $notification->confirmation_receive_date === null) {
                $notification->update(['confirmation_receive_date' => now()]);
            }
        }

        // Legacy fallback: only when the task was created without a workflow and still
        // has no active process, mark it approved directly.
        if (! $this->taskHasActiveProcess($task->id) && $task->status === EmployeeTaskStatus::Pending->value) {
            $task->update([
                'status' => EmployeeTaskStatus::Approved->value,
                'approved_at' => now(),
            ]);
        }

        // Once the creation workflow is complete and the task is approved, start it.
        // When independent_progress is enabled, each employee gets their own
        // ConfirmProjectNotificationPresence lifecycle process so the confirm-receive
        // itself becomes a separate lifecycle step.
        if (! $this->taskHasActiveProcess($task->id) && $task->status === EmployeeTaskStatus::Approved->value) {
            if ($notification->independent_progress) {
                $procedureSetting = $this->engine->resolveLifecycleSetting(
                    $dto->internalProcedureSettingId,
                    $task->procedureSettingType()->value,
                    InternalProcessForm::ConfirmProjectNotificationPresence->value,
                    $notification->company_id,
                    $task->user?->userProfessionalData?->branch_id !== null
                        ? (string) $task->user->userProfessionalData->branch_id
                        : null,
                );

                $metadata = [
                    'form' => InternalProcessForm::ConfirmProjectNotificationPresence->value,
                    'latitude' => $dto->latitude,
                    'longitude' => $dto->longitude,
                    'notes' => $dto->notes,
                    'user_id' => (string) $user->id,
                ];

                $process = $this->createLifecycleProcessForNotification(
                    $task,
                    $notification,
                    InternalProcessForm::ConfirmProjectNotificationPresence->value,
                    $metadata,
                    $procedureSetting,
                    (string) $user->id,
                );

                if ($process !== null) {
                    $this->employeeTaskRequestService->approveWorkflowStep($task->id, (string) $user->id);
                    $task = $task->fresh();
                }

                // The listener may have started the task if the workflow completed.
                if ($task->status === EmployeeTaskStatus::InProgress->value && $notification->confirmation_receive_date === null) {
                    $notification->update(['confirmation_receive_date' => now()]);
                }

                return $task->fresh();
            }

            if ($task->hasPendingStartRequest()) {
                throw EmployeeTaskException::pendingStartRequestExists();
            }

            $task = $this->lifecycleService->performStart($task, $dto, $user);

            // Record the exact moment the employee confirmed/received the task.
            if ($notification->confirmation_receive_date === null) {
                $notification->update(['confirmation_receive_date' => now()]);
            }
        }

        return $task->fresh();
    }

    public function startTask(string $notificationId, StartTaskDTO $dto, User $user): EmployeeTaskRequest
    {
        return $this->confirmReceive($notificationId, $dto, $user);
    }

    public function endTask(string $notificationId, EndTaskDTO $dto, ?string $userId = null): EmployeeTaskRequest
    {
        $notification = $this->get($notificationId);
        $this->guardNotDraft($notification, 'end task');
        $task = $notification->employeeTask;

        if (! $task) {
            throw ProjectNotificationException::linkedTaskNotFound($notificationId);
        }

        $endTaskStatus = $dto->statusId
            ? $this->resolveEndTaskStatus($dto->statusId)
            : null;

        if ($endTaskStatus?->key === 'shift_handover') {
            $this->ensureShiftHandoverHasAnotherEmployeeLocationConfirmation($notification, $userId);
        }

        $endTaskStatusId = $endTaskStatus?->id;

        $independentUserId = $notification->independent_progress ? $userId : null;

        // Approved-but-never-started project notifications can be closed directly
        // (e.g. the employee chose to end the task without confirming receipt).
        // However, if there is a pending confirm-receive process (e.g. after
        // reassignment), cancel it first so it doesn't stay orphaned in the inbox.
        if ($task->status === EmployeeTaskStatus::Approved->value) {
            $this->cancelOldConfirmReceiveProcesses($task);
            $task = $this->lifecycleService->performEnd($task, $dto);
        } else {
            $task = $this->lifecycleService->end($task->id, $dto, $independentUserId);
        }

        if (! empty($dto->files)) {
            $this->fileUploadService->uploadFile(
                model: $task,
                file: $dto->files,
                filePath: 'employee-tasks/end-attachments',
                collectionName: 'end_attachments',
                visibility: 'public',
            );
        }

        ProjectNotificationSiteStatusUpdate::query()->create([
            'company_id' => $notification->company_id,
            'project_notification_id' => $notification->id,
            'employee_task_request_id' => $task->id,
            'description' => 'تم الانهاء من الاعمال',
            'status' => 'approved',
            'requested_by' => $task->user_id,
            'update_date' => now()->toDateString(),
            'update_time' => now()->format('H:i:s'),
        ]);

        if ($endTaskStatusId) {
            $notification->update(['end_task_status_id' => $endTaskStatusId]);
        }

        return $task->fresh()->load('media');
    }

    /**
     * Reassign the linked task to one or more employees and reset it so each
     * new assignee can start a fresh lifecycle. The supplied user IDs become
     * the notification's assigned users (mirroring creation), the task status is
     * reset to approved, lifecycle markers are cleared, and a per-user creation
     * workflow process is started for every supplied user.
     */
    public function reassignTask(
        string $notificationId,
        array $targetUserIds,
        string $actorUserId,
    ): ProjectNotification {
        if (empty($targetUserIds)) {
            throw ProjectNotificationException::validationFailed('At least one assigned user is required.');
        }

        $notification = $this->get($notificationId);
        $this->guardNotDraft($notification, 'reassign');
        $task = $notification->employeeTask;

        if (! $task) {
            throw ProjectNotificationException::linkedTaskNotFound($notificationId);
        }

        $targetUserIds = array_values(array_unique(array_map('strval', $targetUserIds)));

        $existingUserIds = User::query()
            ->whereIn('id', $targetUserIds)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->toArray();

        foreach ($targetUserIds as $userId) {
            if (! in_array($userId, $existingUserIds, true)) {
                throw ProjectNotificationException::userNotFound();
            }
        }

        $primaryUserId = $targetUserIds[0];

        DB::transaction(function () use ($notification, $task, $primaryUserId, $targetUserIds) {
            $notification->update([
                'assigned_user_ids' => $targetUserIds,
                'independent_progress' => true,
                'confirmation_receive_date' => null,
                'location_confirmed_at' => null,
                'end_task_status_id' => null,
            ]);

            $task->update([
                'user_id' => $primaryUserId,
                'status' => EmployeeTaskStatus::Approved->value,
                'approved_at' => now(),
                'time_from' => null,
                'time_to' => null,
                'total_task_hours' => null,
                'total_pause_minutes' => 0,
                'shift_end_method' => null,
                'start_location' => null,
                'end_location' => null,
                'radius_meters' => null,
                'timezone' => null,
                'current_procedure_step_id' => null,
            ]);

            // Cancel all old in-progress processes for the task so neither
            // previous assignees nor the new assignees have stale workflows.
            $this->cancelOldConfirmReceiveProcesses($task);

            $this->syncNotificationStatusFromTask($notification->fresh(), $task->fresh());
        });

        $notification = $notification->fresh();
        $task = $task->fresh();

        // Start a fresh creation workflow process for every supplied user using
        // the same mechanism and form key as project-notification creation.
        if (
            $task->status === EmployeeTaskStatus::Approved->value
            && $notification->independent_progress
        ) {
            $usersNeedingProcess = array_filter(
                $targetUserIds,
                fn (string $userId) => ! $this->hasPendingProcessForUser($task, $userId),
            );

            if (! empty($usersNeedingProcess)) {
                $this->employeeTaskRequestService->createIndependentProcessesForUsers(
                    $task,
                    $usersNeedingProcess,
                    InternalProcessForm::CreateProjectNotificationTask->value,
                );
            }

            // Fallback: if the central engine auto-approved for a user (no
            // resolvable action takers), seed a minimal pending process so the
            // notification still appears in that user's inbox.
            foreach ($targetUserIds as $userId) {
                if (! $this->hasPendingProcessForUser($task, $userId)) {
                    $this->seedConfirmReceiveProcess($task, $notification, $userId, null);
                }
            }
        }

        return $this->get($notificationId);
    }

    /**
     * Create a per-user ConfirmProjectNotificationPresence process with a single
     * pending step assigned to the target user. This bypasses action-taker
     * resolution so reassignment works regardless of the procedure setting's
     * configured action-taker types.
     */
    private function seedConfirmReceiveProcess(
        EmployeeTaskRequest $task,
        ProjectNotification $notification,
        string $targetUserId,
        ?ProcedureSetting $procedureSetting = null,
    ): void {
        // Use the notification's company_id (always set) instead of the task's
        // company_id, which may be null for project notification tasks.
        $companyId = $notification->company_id ?? $task->company_id;

        // Resolve the target user's branch for branch-specific settings.
        $targetUser = User::query()
            ->with('userProfessionalData.branch')
            ->find($targetUserId);
        $branchId = $targetUser?->userProfessionalData?->branch_id !== null
            ? (string) $targetUser->userProfessionalData->branch_id
            : null;

        if ($procedureSetting === null) {
            $procedureSetting = $this->engine->resolveLifecycleSetting(
                null,
                $task->procedureSettingType()->value,
                InternalProcessForm::ConfirmProjectNotificationPresence->value,
                $companyId,
                $branchId,
            );
        }

        // Resolve the first step from the setting (or its descendants).
        $step = null;
        if ($procedureSetting !== null) {
            $settingIds = [$procedureSetting->id];
            $descendants = $this->collectProcedureSettingDescendantIds($procedureSetting->id);
            $settingIds = array_merge($settingIds, $descendants);

            $step = \Modules\ProcedureSetting\Models\ProcedureSettingStep::query()
                ->whereIn('procedure_setting_id', $settingIds)
                ->orderBy('step_order')
                ->first();
        }

        // Fallback: if no procedure setting was found, use the task's current
        // procedure step (set during notification creation) so we can still
        // create a pending step for the reassigned user.
        if ($step === null && $task->current_procedure_step_id !== null) {
            $step = \Modules\ProcedureSetting\Models\ProcedureSettingStep::query()
                ->find($task->current_procedure_step_id);
        }

        // Find the next free sort_order for this user-scoped process.
        $sortOrder = $procedureSetting->sort_order ?? 1;
        while (Process::query()
            ->where('processable_id', $task->id)
            ->where('processable_type', ProcedureSettingType::ProjectNotificationTask->value)
            ->where('sort_order', $sortOrder)
            ->where('user_id', $targetUserId)
            ->exists()
        ) {
            $sortOrder++;
        }

        $snapshot = [];
        if ($step !== null) {
            $snapshot = [[
                'step_id'               => $step->id,
                'template_step_order'   => $step->step_order,
                'assigned_user_id'      => $targetUserId,
                'authorized_user_ids'   => [$targetUserId],
                'specific_procedure_types' => (array) ($step->action_taker_specific_procedure_type ?? []),
                'action_taker_type'     => $step->action_taker_type?->value,
                'escalation_management_hierarchy_id' => $step->escalation_management_hierarchy_id,
            ]];
        }

        $process = Process::create([
            'processable_type'      => ProcedureSettingType::ProjectNotificationTask->value,
            'processable_id'        => $task->id,
            'user_id'               => $targetUserId,
            'execute_type'          => $procedureSetting?->execute_type ?? 'sequence',
            'status'                => \Modules\Process\Enums\ProcessStatus::InProgress,
            'sort_order'            => $sortOrder,
            'template_snapshot'     => $snapshot,
            'procedure_setting_id'  => $procedureSetting?->id,
            'metadata'              => [
                'form' => InternalProcessForm::ConfirmProjectNotificationPresence->value,
                'user_id' => $targetUserId,
            ],
        ]);

        // Always create a pending step so the process is visible in the user's
        // inbox. Use the resolved step, or a minimal step if none was found.
        $processStep = \Modules\Process\Models\ProcessStep::create([
            'process_id'   => $process->id,
            'step_id'      => $step?->id,
            'template_step_order' => $step?->step_order ?? 1,
            'assigned_user_id'    => $targetUserId,
            'authorized_user_ids' => [$targetUserId],
            'status'       => \Modules\Process\Enums\ProcessStepStatus::Pending,
        ]);

        // Fire the same event the central engine fires so push/voice/email/SMS
        // notifications are dispatched for the reassigned user. Without this the
        // fallback path is silent.
        if ($step !== null) {
            event(new \Modules\ProcedureSetting\Events\WorkflowStepActivated(
                processStep: $processStep,
                templateStep: $step,
                userIds: [$targetUserId],
                context: [
                    'form' => InternalProcessForm::ConfirmProjectNotificationPresence->value,
                    'user_id' => $targetUserId,
                ],
            ));
        }
    }

    /**
     * Resolve the procedure setting that should drive the confirm-receive step
     * after reassignment. First try the dedicated ConfirmProjectNotificationPresence
     * form key; if that is not configured, fall back to the first active child of
     * the notification's internal procedure setting that has steps. This handles
     * setups where confirm-receive is modelled as part of the creation workflow
     * tree without its own form key.
     */
    private function resolveConfirmReceiveProcedureSetting(
        EmployeeTaskRequest $task,
        ProjectNotification $notification,
        ?string $branchId,
    ): ?ProcedureSetting {
        // Prefer the same creation workflow tree so reassign behaves exactly
        // like project-notification creation (same steps, same notifications).
        $byForm = $this->engine->resolveLifecycleSetting(
            null,
            $task->procedureSettingType()->value,
            InternalProcessForm::CreateProjectNotificationTask->value,
            $notification->company_id,
            $branchId,
        );

        if ($byForm !== null) {
            return $byForm;
        }

        // Fallback for setups that keep confirm-receive as its own form.
        $byForm = $this->engine->resolveLifecycleSetting(
            null,
            $task->procedureSettingType()->value,
            InternalProcessForm::ConfirmProjectNotificationPresence->value,
            $notification->company_id,
            $branchId,
        );

        if ($byForm !== null) {
            return $byForm;
        }

        // Last resort: first active child under the internal procedure setting.
        $parentId = $notification->internal_procedure_setting_id;
        if ($parentId === null) {
            return null;
        }

        return ProcedureSetting::query()
            ->where('parent_id', $parentId)
            ->where('is_active', true)
            ->whereHas('steps')
            ->orderBy('sort_order')
            ->first();
    }

    private function collectProcedureSettingDescendantIds(string $parentId): array
    {
        $children = ProcedureSetting::query()
            ->where('parent_id', $parentId)
            ->pluck('id')
            ->all();

        $result = $children;
        foreach ($children as $childId) {
            $result = array_merge($result, $this->collectProcedureSettingDescendantIds($childId));
        }

        return $result;
    }

    /**
     * Ensure a shift_handover end task has been confirmed by another assigned
     * employee. The employee ending the task must not be the same employee who
     * confirmed the location, keeping the handover lifecycle separate from the
     * confirmation lifecycle.
     */
    private function ensureShiftHandoverHasAnotherEmployeeLocationConfirmation(
        ProjectNotification $notification,
        ?string $userId,
    ): void {
        if ($userId === null) {
            throw ProjectNotificationException::shiftHandoverRequiresAnotherEmployeeLocationConfirmation();
        }

        $assignedUserIds = $notification->assigned_user_ids ?? [];

        $hasOtherConfirmed = ProjectNotificationLocationConfirmation::query()
            ->where('project_notification_id', $notification->id)
            ->where('status', 'approved')
            ->where('is_inside_location', true)
            ->where('requested_by', '!=', $userId)
            ->whereIn('requested_by', $assignedUserIds)
            ->exists();

        if (! $hasOtherConfirmed) {
            throw ProjectNotificationException::shiftHandoverRequiresAnotherEmployeeLocationConfirmation();
        }
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
        $notification = $this->get($notificationId);
        $this->guardNotDraft($notification, 'take action');
        $task = $this->linkedTask($notificationId);

        $scopedUserId = $notification->independent_progress ? $userId : null;
        $availableActions = $this->availableActionsService->forTask($task->id, $scopedUserId);
        $availableIds = array_column($availableActions, 'id');

        if (! in_array($procedureSettingId, $availableIds, true)) {
            throw ProjectNotificationException::procedureNotAvailable();
        }

        event(new WorkflowProcedureTaken(
            $task->procedureSettingType()->value,
            $task->id,
            $procedureSettingId,
            $userId,
            userId: $scopedUserId,
        ));

        return ['procedure_setting_id' => $procedureSettingId];
    }

    /**
     * Return all periodic site status updates for a notification.
     *
     * Includes:
     *   - Approved updates (records already created in DB after workflow completed)
     *   - Pending updates (in-progress Processes with form=updateProjectNotificationSiteStatus)
     *
     * Each item includes form_data, approval status, date, and attachments.
     *
     * @return array{items: list<array>, summary: array, timezone: string}
     */
    public function siteStatusUpdates(string $notificationId): array
    {
        $notification = $this->get($notificationId);
        $task = $this->linkedTask($notificationId);

        // Resolve the timezone for this notification: prefer the linked task's
        // stored timezone, then fall back to the current request's branch timezone.
        $timezone = $task->timezone ?? getTimeZoneBranchByRequest() ?? config('app.timezone');

        // 1. Load approved site status update records (created after workflow completed).
        $approvedUpdates = ProjectNotificationSiteStatusUpdate::query()
            ->where('project_notification_id', $notification->id)
            ->with(['requester', 'reviewer', 'media', 'process.steps.actionByUser'])
            ->orderByDesc('created_at')
            ->get();

        // 2. Load pending/in-progress processes for the site status form.
        $pendingProcesses = \Modules\Process\Models\Process::query()
            ->where('processable_type', $task->procedureSettingType()->value)
            ->where('processable_id', $task->id)
            ->where('status', \Modules\Process\Enums\ProcessStatus::InProgress)
            ->whereHas('procedureSetting', function ($q) {
                $q->where('form', InternalProcessForm::UpdateProjectNotificationSiteStatus->value);
            })
            ->with(['steps.procedureSettingStep', 'steps.actionByUser', 'procedureSetting'])
            ->orderByDesc('created_at')
            ->get();

        $items = [];

        // Approved records.
        foreach ($approvedUpdates as $update) {
            $process = $update->process;
            $items[] = [
                'id'                    => $update->id,
                'status'                => 'approved',
                'description'           => $update->description,
                'attachments'           => \Modules\Shared\Media\Presenters\MediaPresenter::collection(
                    $update->getMedia('attachments')
                ),
                'requested_by'          => $update->requester ? [
                    'id'   => $update->requester->id,
                    'name' => $update->requester->name,
                ] : null,
                'reviewed_by'           => $update->reviewer ? [
                    'id'   => $update->reviewer->id,
                    'name' => $update->reviewer->name,
                ] : null,
                'reviewed_at'           => $this->formatInTimezone($update->reviewed_at, $timezone),
                'review_notes'          => $update->review_notes,
                'created_at'            => $this->formatInTimezone($update->created_at, $timezone),
                'process'               => $process ? [
                    'id'     => $process->id,
                    'status' => $process->status?->value,
                    'steps'  => $process->relationLoaded('steps')
                        ? $process->steps->map(fn ($step) => [
                            'step_order' => $step->template_step_order,
                            'status'     => $step->status?->value,
                            'action_by'  => $step->actionByUser ? [
                                'id'   => $step->actionByUser->id,
                                'name' => $step->actionByUser->name,
                            ] : null,
                            'acted_at'   => $this->formatInTimezone($step->acted_at, $timezone),
                        ])->toArray()
                        : [],
                ] : null,
            ];
        }

        // Pending processes (not yet approved — data is in process metadata).
        foreach ($pendingProcesses as $process) {
            $metadata = $process->metadata ?? [];
            $updateData = $metadata['update'] ?? [];

            // Collect staged file IDs from metadata.
            $fileIds = $metadata['files'] ?? [];
            $attachments = [];
            if (is_array($fileIds) && $fileIds !== []) {
                $mediaItems = \Modules\Shared\Media\Models\CustomMedia::query()
                    ->whereIn('id', array_map('intval', $fileIds))
                    ->get();
                $attachments = \Modules\Shared\Media\Presenters\MediaPresenter::collection($mediaItems);
            }

            $items[] = [
                'id'                    => $process->id,
                'status'                => 'pending',
                'description'           => $updateData['description'] ?? null,
                'attachments'           => $attachments,
                'requested_by'          => isset($metadata['user_id'])
                    ? [
                        'id'   => $metadata['user_id'],
                        'name' => \Modules\User\Models\User::find($metadata['user_id'])?->name,
                    ]
                    : null,
                'reviewed_by'           => null,
                'reviewed_at'           => null,
                'review_notes'          => null,
                'created_at'            => $this->formatInTimezone($process->created_at, $timezone),
                'process'               => [
                    'id'     => $process->id,
                    'status' => $process->status?->value,
                    'steps'  => $process->relationLoaded('steps')
                        ? $process->steps->map(fn ($step) => [
                            'step_order' => $step->template_step_order,
                            'name'       => $step->procedureSettingStep?->name,
                            'status'     => $step->status?->value,
                            'action_by'  => $step->actionByUser ? [
                                'id'   => $step->actionByUser->id,
                                'name' => $step->actionByUser->name,
                            ] : null,
                            'acted_at'   => $this->formatInTimezone($step->acted_at, $timezone),
                        ])->toArray()
                        : [],
                ],
            ];
        }

        // Sort all items by created_at descending.
        usort($items, fn ($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));

        // Include any site-status files still staged on the notification. These can
        // be left behind when the workflow listener's strict in_array comparison
        // fails to match string file IDs, so we surface them as attachments on the
        // most recent approved record to keep the mobile app working.
        $stagedMedia = $notification->getMedia('site_status_update_attachments');
        if ($stagedMedia->isNotEmpty() && $items !== []) {
            $firstApprovedIndex = null;
            foreach ($items as $index => $item) {
                if ($item['status'] === 'approved') {
                    $firstApprovedIndex = $index;
                    break;
                }
            }

            if ($firstApprovedIndex !== null) {
                $items[$firstApprovedIndex]['attachments'] = array_merge(
                    $items[$firstApprovedIndex]['attachments'],
                    \Modules\Shared\Media\Presenters\MediaPresenter::collection($stagedMedia),
                );
            }
        }

        $summary = [
            'total'    => count($items),
            'approved' => $approvedUpdates->count(),
            'pending'  => $pendingProcesses->count(),
        ];

        return [
            'items'    => $items,
            'summary'  => $summary,
            'timezone' => $timezone,
        ];
    }

    /**
     * Convert a UTC Carbon datetime to the requested timezone string.
     */
    private function formatInTimezone(?Carbon $date, string $timezone): ?string
    {
        if (! $date) {
            return null;
        }

        return $date->setTimezone($timezone)->format('Y-m-d H:i:s');
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
     * Evaluate precondition-type conditions for a project-notification workflow form.
     * Currently enforces InsideTaskLocation when it is active on the form's procedure
     * setting and the request supplies current GPS coordinates.
     *
     * @throws EmployeeTaskException
     */
    private function checkWorkflowFormConditions(
        string $formKey,
        EmployeeTaskRequest $task,
        ProjectNotification $notification,
        ?float $currentLatitude,
        ?float $currentLongitude,
    ): void {
        if ($currentLatitude === null || $currentLongitude === null) {
            return;
        }

        $task->loadMissing('user.userProfessionalData');
        $branchId = $task->user?->userProfessionalData?->branch_id !== null
            ? (string) $task->user->userProfessionalData->branch_id
            : null;

        $ctx = new ConditionContext(
            userId: (string) $task->user_id,
            companyId: (string) $task->company_id,
            branchId: $branchId,
            currentLatitude: $currentLatitude,
            currentLongitude: $currentLongitude,
            taskLatitude: $notification->task_latitude !== null ? (float) $notification->task_latitude : null,
            taskLongitude: $notification->task_longitude !== null ? (float) $notification->task_longitude : null,
        );

        $this->conditionService->checkFormConditions($formKey, $ctx);
    }

    private function createWorkStoppageReportRecord(
        ProjectNotification $notification,
        EmployeeTaskRequest $task,
        RequestProjectNotificationWorkStoppageReportDTO $dto,
        string $userId,
    ): ProjectNotificationWorkStoppageReport {
        $report = ProjectNotificationWorkStoppageReport::query()->create([
            'company_id' => $notification->company_id,
            'project_notification_id' => $notification->id,
            'employee_task_request_id' => $task->id,
            'requested_by' => $userId,
            'status' => 'approved',
            'other_notes' => $dto->otherNotes,
        ]);

        foreach ($dto->reasons as $index => $reason) {
            $reasonModel = ! empty($reason['reason_id'])
                ? ProjectNotificationWorkStoppageReason::query()->find($reason['reason_id'])
                : null;

            ProjectNotificationWorkStoppageReportReason::query()->create([
                'project_notification_work_stoppage_report_id' => $report->id,
                'work_stoppage_reason_id' => $reasonModel?->id,
                'reason_name_ar' => $reasonModel?->name_ar ?? null,
                'reason_name_en' => $reasonModel?->name_en ?? null,
                'notes' => $reason['notes'] ?? null,
                'sort_order' => $reason['sort_order'] ?? ($index + 1),
            ]);
        }

        $this->attachWorkStoppageReportFiles($report, $dto->files);

        return $report;
    }

    /**
     * @param array<int, \Illuminate\Http\UploadedFile>|null $files
     * @return list<int>
     */
    private function stageWorkStoppageReportFiles(ProjectNotification $notification, ?array $files): array
    {
        if (empty($files)) {
            return [];
        }

        $media = $this->fileUploadService->uploadFile(
            model: $notification,
            file: $files,
            filePath: 'project-notifications/work-stoppage-reports',
            collectionName: 'work_stoppage_report_attachments',
            visibility: 'public',
        );

        return $media->pluck('id')->all();
    }

    /**
     * @param array<int, \Illuminate\Http\UploadedFile>|null $files
     */
    private function attachWorkStoppageReportFiles(ProjectNotificationWorkStoppageReport $report, ?array $files): void
    {
        if (empty($files)) {
            return;
        }

        $this->fileUploadService->uploadFile(
            model: $report,
            file: $files,
            filePath: 'project-notifications/work-stoppage-reports',
            collectionName: 'attachments',
            visibility: 'public',
        );
    }

    private function createWorkResumptionRecord(
        ProjectNotification $notification,
        EmployeeTaskRequest $task,
        RequestProjectNotificationWorkResumptionDTO $dto,
        string $userId,
    ): ProjectNotificationWorkResumption {
        $resumption = ProjectNotificationWorkResumption::query()->create([
            'company_id' => $notification->company_id,
            'project_notification_id' => $notification->id,
            'employee_task_request_id' => $task->id,
            'requested_by' => $userId,
            'status' => 'approved',
            'reasons_resolved' => $dto->reasonsResolved,
            'safety_notes_reviewed' => $dto->safetyNotesReviewed,
            'site_ready' => $dto->siteReady,
            'contractor_notified' => $dto->contractorNotified,
            'notes' => $dto->notes,
        ]);

        $this->attachWorkResumptionFiles($resumption, $dto->files);

        return $resumption;
    }

    /**
     * @param array<int, \Illuminate\Http\UploadedFile>|null $files
     * @return list<int>
     */
    private function stageWorkResumptionFiles(ProjectNotification $notification, ?array $files): array
    {
        if (empty($files)) {
            return [];
        }

        $media = $this->fileUploadService->uploadFile(
            model: $notification,
            file: $files,
            filePath: 'project-notifications/work-resumptions',
            collectionName: 'work_resumption_attachments',
            visibility: 'public',
        );

        return $media->pluck('id')->all();
    }

    /**
     * @param array<int, \Illuminate\Http\UploadedFile>|null $files
     */
    private function attachWorkResumptionFiles(ProjectNotificationWorkResumption $resumption, ?array $files): void
    {
        if (empty($files)) {
            return;
        }

        $this->fileUploadService->uploadFile(
            model: $resumption,
            file: $files,
            filePath: 'project-notifications/work-resumptions',
            collectionName: 'attachments',
            visibility: 'public',
        );
    }

    private function createLocationConfirmationRecord(
        ProjectNotification $notification,
        EmployeeTaskRequest $task,
        RequestProjectNotificationLocationConfirmationDTO $dto,
        string $userId,
    ): ProjectNotificationLocationConfirmation {
        $confirmation = ProjectNotificationLocationConfirmation::query()->create([
            'company_id' => $notification->company_id,
            'project_notification_id' => $notification->id,
            'employee_task_request_id' => $task->id,
            'requested_by' => $userId,
            'status' => 'approved',
            ...$dto->toArray(),
        ]);

        // Record the exact moment the employee confirmed the location. Once set,
        // the notification's "in_progress" status is displayed as "تم تأكيد الموقع"
        // (Confirmed Location) instead of "تم الاستلام" (Received).
        if ($notification->location_confirmed_at === null) {
            $notification->update(['location_confirmed_at' => now()]);
        }

        return $confirmation;
    }

    private function createFineRecord(
        ProjectNotification $notification,
        EmployeeTaskRequest $task,
        RequestProjectNotificationFineDTO $dto,
        string $userId,
    ): ProjectNotificationFine {
        $fine = ProjectNotificationFine::query()->create([
            'company_id' => $notification->company_id,
            'project_notification_id' => $notification->id,
            'employee_task_request_id' => $task->id,
            'requested_by' => $userId,
            'status' => 'approved',
            'reason' => $dto->reason,
            'total_amount' => $dto->totalAmount(),
        ]);

        foreach ($dto->items as $index => $item) {
            ProjectNotificationFineItem::query()->create([
                'project_notification_fine_id' => $fine->id,
                'name_ar' => $item['name_ar'],
                'name_en' => $item['name_en'] ?? null,
                'quantity' => $item['quantity'],
                'unit_amount' => $item['unit_amount'],
                'total_amount' => $item['total_amount'],
                'sort_order' => $item['sort_order'] ?? ($index + 1),
            ]);
        }

        $this->attachFineFiles($fine, $dto->files);

        return $fine;
    }

    /**
     * @param array<int, \Illuminate\Http\UploadedFile>|null $files
     * @return list<int>
     */
    private function stageFineFiles(ProjectNotification $notification, ?array $files): array
    {
        if (empty($files)) {
            return [];
        }

        $media = $this->fileUploadService->uploadFile(
            model: $notification,
            file: $files,
            filePath: 'project-notifications/fines',
            collectionName: 'fine_attachments',
            visibility: 'public',
        );

        return $media->pluck('id')->all();
    }

    /**
     * @param array<int, \Illuminate\Http\UploadedFile>|null $files
     */
    private function attachFineFiles(ProjectNotificationFine $fine, ?array $files): void
    {
        if (empty($files)) {
            return;
        }

        $this->fileUploadService->uploadFile(
            model: $fine,
            file: $files,
            filePath: 'project-notifications/fines',
            collectionName: 'attachments',
            visibility: 'public',
        );
    }

    private function createSiteStatusUpdateRecord(
        ProjectNotification $notification,
        EmployeeTaskRequest $task,
        RequestProjectNotificationSiteStatusUpdateDTO $dto,
        string $userId,
    ): ProjectNotificationSiteStatusUpdate {
        $update = ProjectNotificationSiteStatusUpdate::query()->create([
            'company_id' => $notification->company_id,
            'project_notification_id' => $notification->id,
            'employee_task_request_id' => $task->id,
            'requested_by' => $userId,
            'status' => 'approved',
            ...$dto->toArray(),
        ]);

        $this->attachSiteStatusUpdateFiles($update, $dto->files);

        return $update;
    }

    /**
     * @param array<int, \Illuminate\Http\UploadedFile>|null $files
     * @return list<int>
     */
    private function stageSiteStatusUpdateFiles(ProjectNotification $notification, ?array $files): array
    {
        if (empty($files)) {
            return [];
        }

        $media = $this->fileUploadService->uploadFile(
            model: $notification,
            file: $files,
            filePath: 'project-notifications/site-status-updates',
            collectionName: 'site_status_update_attachments',
            visibility: 'public',
        );

        return $media->pluck('id')->all();
    }

    /**
     * @param array<int, \Illuminate\Http\UploadedFile>|null $files
     */
    private function attachSiteStatusUpdateFiles(ProjectNotificationSiteStatusUpdate $update, ?array $files): void
    {
        if (empty($files)) {
            return;
        }

        $this->fileUploadService->uploadFile(
            model: $update,
            file: $files,
            filePath: 'project-notifications/site-status-updates',
            collectionName: 'attachments',
            visibility: 'public',
        );
    }

    /**
     * @param array<int, \Illuminate\Http\UploadedFile>|null $files
     * @return list<int>
     */
    private function stageUpdateFiles(ProjectNotification $notification, ?array $files): array
    {
        if (empty($files)) {
            return [];
        }

        $media = $this->fileUploadService->uploadFile(
            model: $notification,
            file: $files,
            filePath: 'project-notifications/updates',
            collectionName: 'update_attachments',
            visibility: 'public',
        );

        return $media->pluck('id')->all();
    }

    /**
     * @param array<int, \Illuminate\Http\UploadedFile>|null $files
     */
    private function attachUpdateFiles(ProjectNotification $notification, ?array $files): void
    {
        if (empty($files)) {
            return;
        }

        $this->fileUploadService->uploadFile(
            model: $notification,
            file: $files,
            filePath: 'project-notifications/attachments',
            collectionName: 'attachments',
            visibility: 'public',
        );
    }

    /**
     * @param array<int, \Illuminate\Http\UploadedFile>|null $files
     */
    private function attachFilesToNotification(ProjectNotification $notification, ?array $files): void
    {
        $this->attachUpdateFiles($notification, $files);
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
