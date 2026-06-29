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
use Modules\ProcedureSetting\Models\ProcedureSetting;
use Modules\ProcedureSetting\Enums\ProcedureSettingType;
use Modules\ProcedureSetting\Services\ProcedureWorkflowService;
use Modules\Process\Enums\ProcessStatus;
use Modules\Process\Enums\ProcessStepStatus;
use Modules\Process\Models\Process;
use Modules\Project\ProjectManagement\DTO\CreateProjectNotificationDTO;
use Modules\Project\ProjectManagement\DTO\FilterProjectNotificationDTO;
use Modules\Project\ProjectManagement\DTO\RequestProjectNotificationFineDTO;
use Modules\Project\ProjectManagement\DTO\RequestProjectNotificationLocationConfirmationDTO;
use Modules\Project\ProjectManagement\DTO\RequestProjectNotificationSiteStatusUpdateDTO;
use Modules\Project\ProjectManagement\DTO\RequestProjectNotificationUpdateDTO;
use Modules\Project\ProjectManagement\DTO\RequestProjectNotificationWorkStoppageReportDTO;
use Modules\Project\ProjectManagement\DTO\UpdateProjectNotificationDTO;
use Modules\Project\ProjectManagement\Exceptions\ProjectNotificationException;
use Modules\Project\ProjectManagement\Models\Contractor;
use Modules\Project\ProjectManagement\Models\ProjectNotification;
use Modules\Project\ProjectManagement\Models\ProjectNotificationFine;
use Modules\Project\ProjectManagement\Models\ProjectNotificationFineItem;
use Modules\Project\ProjectManagement\Models\ProjectNotificationLocationConfirmation;
use Modules\Project\ProjectManagement\Models\ProjectNotificationSiteStatus;
use Modules\Project\ProjectManagement\Models\ProjectNotificationSiteStatusUpdate;
use Modules\Project\ProjectManagement\Models\ProjectNotificationWorkStoppageReason;
use Modules\Project\ProjectManagement\Models\ProjectNotificationWorkStoppageReport;
use Modules\Project\ProjectManagement\Models\ProjectNotificationWorkStoppageReportReason;
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
        private readonly ProcedureWorkflowService $procedureWorkflow,
    ) {}

    public function create(CreateProjectNotificationDTO $dto): ProjectNotification
    {
        $companyId = (string) tenant('id');
        $creator = User::find($dto->createdByUserId);
        $branchId = $creator?->userProfessionalData?->branch_id !== null
            ? (string) $creator->userProfessionalData->branch_id
            : null;

        $data = $this->enrichContractorData($dto->toArray());

        // 1. Create the ProjectNotification row. notification_number is manual if
        // provided; otherwise the observer auto-generates it.
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
     * List distinct notification types from existing project notifications.
     * Used to populate the notification type dropdown/filter.
     *
     * @return list<array{value: string}>
     */
    public function listNotificationTypes(): array
    {
        return ProjectNotification::query()
            ->whereNotNull('notification_type')
            ->where('notification_type', '!=', '')
            ->distinct()
            ->orderBy('notification_type')
            ->pluck('notification_type')
            ->map(fn ($type) => ['value' => $type])
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
        $task = $this->linkedTask($id);

        $procedureSetting = $dto->internalProcedureSettingId !== null
            ? ProcedureSetting::query()->find($dto->internalProcedureSettingId)
            : $this->procedureWorkflow->resolveInternalProcedureSettingByForm(
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

            return $notification->fresh();
        }

        $metadata = [
            'form'   => InternalProcessForm::UpdateProjectNotificationTask->value,
            'update' => $dto->toArray(),
            'files'  => $this->stageUpdateFiles($notification, $dto->files),
        ];

        $this->employeeTaskRequestService->createLifecycleProcess(
            $task,
            InternalProcessForm::UpdateProjectNotificationTask->value,
            $metadata,
            $procedureSetting,
        );

        return $notification->fresh();
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

        $procedureSetting = $dto->internalProcedureSettingId !== null
            ? ProcedureSetting::query()->find($dto->internalProcedureSettingId)
            : $this->procedureWorkflow->resolveInternalProcedureSettingByForm(
                $task->procedureSettingType()->value,
                InternalProcessForm::UpdateProjectNotificationSiteStatus->value,
                $task->company_id,
                $task->user?->userProfessionalData?->branch_id !== null
                    ? (string) $task->user->userProfessionalData->branch_id
                    : null,
            );

        if ($procedureSetting === null) {
            // No procedure configured → create immediately.
            $this->createSiteStatusUpdateRecord($notification, $task, $dto, $userId);

            return $notification->fresh();
        }

        $metadata = [
            'form' => InternalProcessForm::UpdateProjectNotificationSiteStatus->value,
            'update' => $dto->toArray(),
            'files' => $this->stageSiteStatusUpdateFiles($notification, $dto->files),
            'user_id' => $userId,
        ];

        $this->employeeTaskRequestService->createLifecycleProcess(
            $task,
            InternalProcessForm::UpdateProjectNotificationSiteStatus->value,
            $metadata,
            $procedureSetting,
        );

        return $notification->fresh();
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
        $task = $this->linkedTask($id);

        $procedureSetting = $dto->internalProcedureSettingId !== null
            ? ProcedureSetting::query()->find($dto->internalProcedureSettingId)
            : $this->procedureWorkflow->resolveInternalProcedureSettingByForm(
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

            return $notification->fresh();
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

        $this->employeeTaskRequestService->createLifecycleProcess(
            $task,
            InternalProcessForm::ProjectNotificationFine->value,
            $metadata,
            $procedureSetting,
        );

        return $notification->fresh();
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

        $procedureSetting = $dto->internalProcedureSettingId !== null
            ? ProcedureSetting::query()->find($dto->internalProcedureSettingId)
            : $this->procedureWorkflow->resolveInternalProcedureSettingByForm(
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

            return $notification->fresh();
        }

        $metadata = [
            'form' => InternalProcessForm::ConfirmProjectNotificationLocation->value,
            'update' => $dto->toArray(),
            'user_id' => $userId,
        ];

        $this->employeeTaskRequestService->createLifecycleProcess(
            $task,
            InternalProcessForm::ConfirmProjectNotificationLocation->value,
            $metadata,
            $procedureSetting,
        );

        return $notification->fresh();
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

        $procedureSetting = $dto->internalProcedureSettingId !== null
            ? ProcedureSetting::query()->find($dto->internalProcedureSettingId)
            : $this->procedureWorkflow->resolveInternalProcedureSettingByForm(
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

            return $notification->fresh();
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

        $this->employeeTaskRequestService->createLifecycleProcess(
            $task,
            InternalProcessForm::ProjectNotificationWorkStoppageReport->value,
            $metadata,
            $procedureSetting,
        );

        return $notification->fresh();
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

        $procedureSetting = $dto->internalProcedureSettingId !== null
            ? ProcedureSetting::query()->find($dto->internalProcedureSettingId)
            : $this->procedureWorkflow->resolveInternalProcedureSettingByForm(
                $task->procedureSettingType()->value,
                InternalProcessForm::ProjectNotificationWorkResumption->value,
                $task->company_id,
                $task->user?->userProfessionalData?->branch_id !== null
                    ? (string) $task->user->userProfessionalData->branch_id
                    : null,
            );

        if ($procedureSetting === null) {
            $this->createWorkResumptionRecord($notification, $task, $dto, $userId);

            return $notification->fresh();
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

        $this->employeeTaskRequestService->createLifecycleProcess(
            $task,
            InternalProcessForm::ProjectNotificationWorkResumption->value,
            $metadata,
            $procedureSetting,
        );

        return $notification->fresh();
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

        $procedureSetting = $dto->internalProcedureSettingId !== null
            ? ProcedureSetting::query()->find($dto->internalProcedureSettingId)
            : $this->procedureWorkflow->resolveInternalProcedureSettingByForm(
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

            return $notification->fresh();
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

        $this->employeeTaskRequestService->createLifecycleProcess(
            $task,
            InternalProcessForm::ProjectNotificationTaskPostponement->value,
            $metadata,
            $procedureSetting,
        );

        return $notification->fresh();
    }

    public function delete(string $id): bool
    {
        $notification = $this->get($id);

        return $this->repository->delete($id);
    }

    public function approve(string $id, string $userId, ?string $procedureSettingId = null): ProjectNotification
    {
        $notification = $this->get($id);
        $task = $notification->employee_task_request_id ? $notification->employeeTask : null;

        // When the linked task is driven by a real approval workflow, advance the
        // workflow step regardless of the notification status. This allows the
        // dashboard to approve subsequent steps (confirm-receive, end, etc.) after
        // the task is already in_progress. The EmployeeTaskStatusSyncObserver
        // mirrors the resulting task status onto the notification once the
        // workflow resolves.
        if ($task && $this->taskHasActiveProcess($task->id, $procedureSettingId)) {
            $this->employeeTaskRequestService->approveWorkflowStep($task->id, $userId, $procedureSettingId);

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

    public function reject(string $id, string $userId, string $reason, ?string $procedureSettingId = null): ProjectNotification
    {
        $notification = $this->get($id);
        $task = $notification->employee_task_request_id ? $notification->employeeTask : null;

        if ($task && $this->taskHasActiveProcess($task->id, $procedureSettingId)) {
            $this->employeeTaskRequestService->rejectWorkflowStep($task->id, $userId, $reason, $procedureSettingId);

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

    /**
     * Resolve the in-progress processes that have a pending step assigned to the
     * given user. Used by the mobile inbox to show which workflow(s) need action.
     *
     * @return list<array{process_id: string, procedure_setting_id: string, form: string, pending_step_id: string, pending_step_order: int}>
     */
    public function resolvePendingProcessesForInbox(ProjectNotification $notification, string $userId): array
    {
        $task = $notification->employeeTask;

        if ($task === null || ! $task->relationLoaded('processes')) {
            return [];
        }

        $result = [];
        foreach ($task->processes as $process) {
            if ($process->status !== ProcessStatus::InProgress) {
                continue;
            }

            $pendingStep = $process->steps->first(function ($step) use ($userId) {
                if ($step->status !== ProcessStepStatus::Pending) {
                    return false;
                }

                if ($step->assigned_user_id === $userId) {
                    return true;
                }

                $authorized = $step->authorized_user_ids ?? [];

                return in_array($userId, $authorized, true);
            });

            if ($pendingStep) {
                $result[] = [
                    'process_id' => $process->id,
                    'procedure_setting_id' => $process->procedure_setting_id,
                    'form' => $process->metadata['form'] ?? null,
                    'pending_step_id' => $pendingStep->id,
                    'pending_step_order' => $pendingStep->template_step_order,
                ];
            }
        }

        return $result;
    }

    private function taskHasActiveProcess(string $taskId, ?string $procedureSettingId = null): bool
    {
        $query = Process::query()
            ->where('processable_type', ProcedureSettingType::ProjectNotificationTask->value)
            ->where('processable_id', $taskId)
            ->where('status', ProcessStatus::InProgress);

        if ($procedureSettingId !== null) {
            $query->where('procedure_setting_id', $procedureSettingId);
        }

        return $query->exists();
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

        $ids = [];
        foreach ($files as $file) {
            $media = $notification->addMedia($file)
                ->toMediaCollection('work_stoppage_report_attachments');
            $ids[] = $media->id;
        }

        return $ids;
    }

    /**
     * @param array<int, \Illuminate\Http\UploadedFile>|null $files
     */
    private function attachWorkStoppageReportFiles(ProjectNotificationWorkStoppageReport $report, ?array $files): void
    {
        if (empty($files)) {
            return;
        }

        foreach ($files as $file) {
            $report->addMedia($file)
                ->toMediaCollection('attachments');
        }
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

        $ids = [];
        foreach ($files as $file) {
            $media = $notification->addMedia($file)
                ->toMediaCollection('work_resumption_attachments');
            $ids[] = $media->id;
        }

        return $ids;
    }

    /**
     * @param array<int, \Illuminate\Http\UploadedFile>|null $files
     */
    private function attachWorkResumptionFiles(ProjectNotificationWorkResumption $resumption, ?array $files): void
    {
        if (empty($files)) {
            return;
        }

        foreach ($files as $file) {
            $resumption->addMedia($file)
                ->toMediaCollection('attachments');
        }
    }

    private function createLocationConfirmationRecord(
        ProjectNotification $notification,
        EmployeeTaskRequest $task,
        RequestProjectNotificationLocationConfirmationDTO $dto,
        string $userId,
    ): ProjectNotificationLocationConfirmation {
        return ProjectNotificationLocationConfirmation::query()->create([
            'company_id' => $notification->company_id,
            'project_notification_id' => $notification->id,
            'employee_task_request_id' => $task->id,
            'requested_by' => $userId,
            'status' => 'approved',
            ...$dto->toArray(),
        ]);
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

        $ids = [];
        foreach ($files as $file) {
            $media = $notification->addMedia($file)
                ->toMediaCollection('fine_attachments');
            $ids[] = $media->id;
        }

        return $ids;
    }

    /**
     * @param array<int, \Illuminate\Http\UploadedFile>|null $files
     */
    private function attachFineFiles(ProjectNotificationFine $fine, ?array $files): void
    {
        if (empty($files)) {
            return;
        }

        foreach ($files as $file) {
            $fine->addMedia($file)
                ->toMediaCollection('attachments');
        }
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

        $ids = [];
        foreach ($files as $file) {
            $media = $notification->addMedia($file)
                ->toMediaCollection('site_status_update_attachments');
            $ids[] = $media->id;
        }

        return $ids;
    }

    /**
     * @param array<int, \Illuminate\Http\UploadedFile>|null $files
     */
    private function attachSiteStatusUpdateFiles(ProjectNotificationSiteStatusUpdate $update, ?array $files): void
    {
        if (empty($files)) {
            return;
        }

        foreach ($files as $file) {
            $update->addMedia($file)
                ->toMediaCollection('attachments');
        }
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

        $ids = [];
        foreach ($files as $file) {
            $media = $notification->addMedia($file)
                ->toMediaCollection('update_attachments');
            $ids[] = $media->id;
        }

        return $ids;
    }

    /**
     * @param array<int, \Illuminate\Http\UploadedFile>|null $files
     */
    private function attachUpdateFiles(ProjectNotification $notification, ?array $files): void
    {
        if (empty($files)) {
            return;
        }

        foreach ($files as $file) {
            $notification->addMedia($file)
                ->toMediaCollection('attachments');
        }
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
