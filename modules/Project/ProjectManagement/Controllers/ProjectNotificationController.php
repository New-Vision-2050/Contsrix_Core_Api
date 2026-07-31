<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Modules\EmployeeTask\DTO\EndTaskDTO;
use Modules\EmployeeTask\DTO\StartTaskDTO;
use Modules\EmployeeTask\Enums\EmployeeTaskStatus;
use Modules\EmployeeTask\Exceptions\EmployeeTaskException;
use Modules\EmployeeTask\Presenters\EmployeeTaskRequestPresenter;
use Modules\EmployeeTask\Presenters\TaskProcedurePresenter;
use Modules\EmployeeTask\Requests\EndTaskRequest;
use Modules\EmployeeTask\Requests\StartTaskRequest;
use Modules\Project\ProjectManagement\Exceptions\ProjectNotificationException;
use Modules\Project\ProjectManagement\Exports\ProjectNotificationExport;
use Modules\Project\ProjectManagement\Presenters\ProjectNotificationChartsPresenter;
use Modules\Project\ProjectManagement\Presenters\ProjectNotificationEmployeeLocationPresenter;
use Modules\Project\ProjectManagement\Models\ProjectNotification;
use Modules\Project\ProjectManagement\Presenters\ProjectNotificationPresenter;
use Modules\Project\ProjectManagement\Requests\CreateProjectNotificationRequest;
use Modules\Project\ProjectManagement\Requests\FilterProjectNotificationChartsRequest;
use Modules\Project\ProjectManagement\Requests\FilterProjectNotificationsRequest;
use Modules\Project\ProjectManagement\Requests\GetProjectNotificationEmployeesRequest;
use Modules\Project\ProjectManagement\Requests\ReassignProjectNotificationRequest;
use Modules\Project\ProjectManagement\Requests\RequestProjectNotificationFineRequest;
use Modules\Project\ProjectManagement\Requests\RequestProjectNotificationLocationConfirmationRequest;
use Modules\Project\ProjectManagement\Requests\RequestProjectNotificationSafetyViolationRequest;
use Modules\Project\ProjectManagement\Requests\RequestProjectNotificationSiteStatusUpdateRequest;
use Modules\Project\ProjectManagement\Requests\RequestProjectNotificationTaskPostponementRequest;
use Modules\Project\ProjectManagement\Requests\RequestProjectNotificationUpdateRequest;
use Modules\Project\ProjectManagement\Requests\RequestProjectNotificationWorkResumptionRequest;
use Modules\Project\ProjectManagement\Requests\RequestProjectNotificationWorkStoppageReportRequest;
use Modules\Project\ProjectManagement\Requests\UpdateProjectNotificationRequest;
use Modules\Project\ProjectManagement\Services\ProjectNotificationChartsService;
use Modules\Project\ProjectManagement\Services\ProjectNotificationLocationService;
use Modules\Project\ProjectManagement\Services\ProjectNotificationService;
use Modules\RoleAndPermission\Enums\Permission;

class ProjectNotificationController extends Controller
{
    public function __construct(
        private readonly ProjectNotificationService $notificationService,
        private readonly ProjectNotificationLocationService $locationService,
        private readonly ProjectNotificationChartsService $chartsService,
    ) {}

    /**
     * GET /projects/notifications/notification-types
     *
     * Returns distinct notification types from existing records for dropdown/filter.
     */
    public function notificationTypes(Request $request): JsonResponse
    {
        $types = $this->notificationService->listNotificationTypes();

        return Json::items(
            $types,
            message: 'Notification types retrieved successfully',
        );
    }

    /**
     * GET /projects/notifications/charts
     *
     * Returns aggregated, cross-filterable chart data for project notifications.
     * Each dimension is aggregated excluding its own filter so the frontend can
     * display cross-filtering UX (selecting a value in one chart updates all
     * other charts while keeping the selected chart's full distribution visible).
     */
    public function charts(FilterProjectNotificationChartsRequest $request): JsonResponse
    {
        $chartsData = $this->chartsService->getChartsData($request->toDTO());

        $presentedData = ProjectNotificationChartsPresenter::presentCharts($chartsData);

        return Json::item($presentedData);
    }

    public function index(FilterProjectNotificationsRequest $request): JsonResponse
    {
        $paginator = $this->notificationService->list($request->toDTO());

        $this->notificationService->attachReadStatus($paginator->items(), (string) Auth::id());

        return Json::items(
            ProjectNotificationPresenter::collection($paginator->items()),
            paginationSettings: [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
        );
    }

    /**
     * GET /projects/notifications/map-tasks
     *
     * Map view: all notifications without pagination, with coordinates, radius,
     * task name, assigned user and receive date formatted in the assigned user's
     * branch timezone. Supports:
     *   - status filter via ?status=...
     *   - task date range via ?date_from=YYYY-MM-DD&date_to=YYYY-MM-DD
     */
    public function mapTasks(FilterProjectNotificationsRequest $request): JsonResponse
    {
        $notifications = $this->notificationService->mapTasks($request->toDTO());

        $this->notificationService->attachReadStatus($notifications, (string) Auth::id());

        return Json::item(
            [
                'items' => ProjectNotificationPresenter::mapCollection($notifications),
                'statuses' => ProjectNotificationPresenter::statusLookup(),
            ],
            message: 'Map tasks retrieved successfully',
        );
    }

    /**
     * GET /projects/notifications/site-statuses
     *
     * Returns the active site statuses dropdown for the periodic site status update form.
     */
    public function siteStatuses(Request $request): JsonResponse
    {
        $statuses = $this->notificationService->listSiteStatuses();

        return Json::items(
            $statuses->map(static fn ($status) => [
                'id' => $status->id,
                'name_ar' => $status->name_ar,
                'name_en' => $status->name_en,
                'sort_order' => $status->sort_order,
            ])->toArray(),
            message: 'Site statuses retrieved successfully',
        );
    }

    /**
     * GET /projects/notifications/update-site-statuses
     *
     * Returns the active update site statuses dropdown for the site status update form.
     */
    public function updateSiteStatuses(Request $request): JsonResponse
    {
        $statuses = $this->notificationService->listUpdateSiteStatuses();

        return Json::items(
            $statuses->map(static fn ($status) => [
                'id' => $status->id,
                'key' => $status->key,
                'name_ar' => $status->name_ar,
                'name_en' => $status->name_en,
                'sort_order' => $status->sort_order,
            ])->toArray(),
            message: 'Update site statuses retrieved successfully',
        );
    }

    /**
     * GET /projects/notifications/end-task-statuses
     *
     * Returns the active end task statuses dropdown for the end task form.
     */
    public function endTaskStatuses(Request $request): JsonResponse
    {
        $statuses = $this->notificationService->listEndTaskStatuses();

        return Json::items(
            $statuses->map(static fn ($status) => [
                'id' => $status->id,
                'key' => $status->key,
                'name_ar' => $status->name_ar,
                'name_en' => $status->name_en,
                'sort_order' => $status->sort_order,
            ])->toArray(),
            message: 'End task statuses retrieved successfully',
        );
    }

    /**
     * POST /projects/notifications/{id}/update-site-status
     *
     * Update the site status of a project notification. Accepts either a status
     * UUID or the status unique key.
     */
    public function updateSiteStatus(Request $request): JsonResponse
    {
        $request->validate([
            'status_id' => ['required_without:status_key', 'string'],
            'status_key' => ['required_without:status_id', 'string'],
        ]);

        try {
            $notification = $this->notificationService->updateSiteStatus(
                $request->route('id'),
                $request->input('status_id') ?? $request->input('status_key'),
            );

            return Json::item(
                $this->detailWithReadStatus($notification),
                message: 'Site status updated successfully',
            );
        } catch (ProjectNotificationException $e) {
            return Json::error($e->getMessage(), $e->getCode() ?: 422);
        }
    }

    /**
     * POST /projects/notifications/{id}/end-task-status
     *
     * Update the end task status of a project notification. Accepts either a
     * status UUID or the status unique key.
     */
    public function updateEndTaskStatus(Request $request): JsonResponse
    {
        $request->validate([
            'status_id' => ['required_without:status_key', 'string'],
            'status_key' => ['required_without:status_id', 'string'],
        ]);

        try {
            $notification = $this->notificationService->updateEndTaskStatus(
                $request->route('id'),
                $request->input('status_id') ?? $request->input('status_key'),
            );

            return Json::item(
                $this->detailWithReadStatus($notification),
                message: 'End task status updated successfully',
            );
        } catch (ProjectNotificationException $e) {
            return Json::error($e->getMessage(), $e->getCode() ?: 422);
        }
    }

    /**
     * GET /projects/notifications/work-stoppage-reasons
     *
     * Returns the active work stoppage reasons dropdown for the work stoppage report form.
     */
    public function workStoppageReasons(Request $request): JsonResponse
    {
        $reasons = $this->notificationService->listWorkStoppageReasons();

        return Json::items(
            $reasons->map(static fn ($reason) => [
                'id' => $reason->id,
                'name_ar' => $reason->name_ar,
                'name_en' => $reason->name_en,
                'sort_order' => $reason->sort_order,
            ])->toArray(),
            message: 'Work stoppage reasons retrieved successfully',
        );
    }

    public function store(CreateProjectNotificationRequest $request): JsonResponse
    {
        $notification = $this->notificationService->create($request->toDTO());

        $this->notificationService->attachReadStatus([$notification], (string) Auth::id());

        return Json::item(
            $this->detailWithReadStatus($notification)
        );
    }

    public function show(Request $request): JsonResponse
    {
        $notification = $this->notificationService->get($request->route('id'));

        if (
            $notification->status === 'draft'
            && (string) $notification->created_by_user_id !== (string) $request->user()->id
        ) {
            return Json::error('Project notification not found', 404);
        }

        $this->notificationService->attachReadStatus([$notification], (string) Auth::id());

        return Json::item($this->detailWithReadStatus($notification));
    }

    public function update(UpdateProjectNotificationRequest $request): JsonResponse
    {
        $existing = $this->notificationService->get($request->route('id'));

        if ($existing->status === 'draft') {
            if (! $request->user()->can(Permission::PROJECT_NOTIFICATION_CREATE())) {
                return Json::error('Forbidden', 403);
            }

            if ((string) $existing->created_by_user_id !== (string) $request->user()->id) {
                return Json::error('Project notification not found', 404);
            }
        }

        $notification = $this->notificationService->update(
            $request->route('id'),
            $request->toDTO(),
        );

        $this->notificationService->attachReadStatus([$notification], (string) Auth::id());

        return Json::item($this->detailWithReadStatus($notification));
    }

    /**
     * POST /projects/notifications/{id}/request-update
     *
     * Submit a workflow-based update request. The new data is stored in the
     * Process metadata; the actual ProjectNotification row is updated only after
     * all workflow steps are approved.
     */
    public function requestUpdate(RequestProjectNotificationUpdateRequest $request): JsonResponse
    {
        $notification = $this->notificationService->requestUpdate(
            $request->route('id'),
            $request->toDTO(),
            (string) $request->user()->id,
        );

        return Json::item(
            $this->detailWithReadStatus($notification),
            message: 'Update request submitted successfully',
        );
    }

    /**
     * POST /projects/notifications/{id}/request-site-status-update
     *
     * Submit a workflow-based periodic site status update. The new data is stored
     * in the Process metadata; the actual site status update record is created only
     * after all workflow steps are approved.
     */
    public function requestSiteStatusUpdate(RequestProjectNotificationSiteStatusUpdateRequest $request): JsonResponse
    {
        $notification = $this->notificationService->requestSiteStatusUpdate(
            $request->route('id'),
            $request->toDTO(),
            (string) $request->user()->id,
        );

        return Json::item(
            $this->detailWithReadStatus($notification),
            message: 'Site status update request submitted successfully',
        );
    }

    /**
     * POST /projects/notifications/{id}/request-safety-violation
     *
     * Submit a workflow-based safety violation evaluation. The raw SafetyRecord
     * was already created when the notification was published; this fills in
     * the violation data. The actual violations are synced only after all
     * workflow steps are approved.
     */
    public function requestSafetyViolation(RequestProjectNotificationSafetyViolationRequest $request): JsonResponse
    {
        $notification = $this->notificationService->requestSafetyViolation(
            $request->route('id'),
            $request->toDTO(),
            (string) $request->user()->id,
        );

        return Json::item(
            $this->detailWithReadStatus($notification),
            message: 'Safety violation request submitted successfully',
        );
    }

    /**
     * POST /projects/notifications/{id}/notify-site-status-update-by-voice
     *
     * Trigger a voice call to the assigned user reminding them to update the
     * site status for this project notification.
     */
    public function notifySiteStatusUpdateByVoice(Request $request): JsonResponse
    {
        try {
            $notification = $this->notificationService->notifySiteStatusUpdateByVoice(
                $request->route('id'),
            );

            return Json::item(
                $this->detailWithReadStatus($notification),
                message: 'Voice reminder sent successfully',
            );
        } catch (ProjectNotificationException $e) {
            return Json::error($e->getMessage(), $e->getCode() ?: 422);
        }
    }

    /**
     * POST /projects/notifications/{id}/request-fine
     *
     * Submit a workflow-based fine request. The fine data is stored in the Process
     * metadata; the actual fine record is created only after all workflow steps
     * are approved.
     */
    public function requestFine(RequestProjectNotificationFineRequest $request): JsonResponse
    {
        $notification = $this->notificationService->requestFine(
            $request->route('id'),
            $request->toDTO(),
            (string) $request->user()->id,
        );

        return Json::item(
            $this->detailWithReadStatus($notification),
            message: 'Fine request submitted successfully',
        );
    }

    /**
     * POST /projects/notifications/{id}/confirm-location
     *
     * Submit a workflow-based location confirmation. The location data is stored in
     * the Process metadata; the actual confirmation record is created only after all
     * workflow steps are approved.
     */
    public function confirmLocation(RequestProjectNotificationLocationConfirmationRequest $request): JsonResponse
    {
        $notification = $this->notificationService->requestLocationConfirmation(
            $request->route('id'),
            $request->toDTO(),
            (string) $request->user()->id,
        );

        return Json::item(
            $this->detailWithReadStatus($notification),
            message: 'Location confirmation request submitted successfully',
        );
    }

    /**
     * POST /projects/notifications/{id}/request-work-stoppage-report
     *
     * Submit a workflow-based work stoppage report. The report data is stored in
     * the Process metadata; the actual report record is created only after all
     * workflow steps are approved.
     */
    public function requestWorkStoppageReport(RequestProjectNotificationWorkStoppageReportRequest $request): JsonResponse
    {
        $notification = $this->notificationService->requestWorkStoppageReport(
            $request->route('id'),
            $request->toDTO(),
            (string) $request->user()->id,
        );

        return Json::item(
            $this->detailWithReadStatus($notification),
            message: 'Work stoppage report request submitted successfully',
        );
    }

    /**
     * POST /projects/notifications/{id}/request-work-resumption
     *
     * Submit a workflow-based work resumption. The resumption data is stored in
     * the Process metadata; the actual record is created only after all workflow
     * steps are approved.
     */
    public function requestWorkResumption(RequestProjectNotificationWorkResumptionRequest $request): JsonResponse
    {
        $notification = $this->notificationService->requestWorkResumption(
            $request->route('id'),
            $request->toDTO(),
            (string) $request->user()->id,
        );

        return Json::item(
            $this->detailWithReadStatus($notification),
            message: 'Work resumption request submitted successfully',
        );
    }

    /**
     * POST /projects/notifications/{id}/request-task-postponement
     *
     * Submit a workflow-based task postponement. On approval, the linked task's
     * date and time are updated to the requested values.
     */
    public function requestTaskPostponement(RequestProjectNotificationTaskPostponementRequest $request): JsonResponse
    {
        $notification = $this->notificationService->requestTaskPostponement(
            $request->route('id'),
            $request->toDTO(),
            (string) $request->user()->id,
        );

        return Json::item(
            $this->detailWithReadStatus($notification),
            message: 'Task postponement request submitted successfully',
        );
    }

    public function destroy(Request $request): JsonResponse
    {
        $this->notificationService->delete($request->route('id'));

        return Json::deleted();
    }

    public function export(FilterProjectNotificationsRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'project_notifications.' . $format;

        return Excel::download(
            new ProjectNotificationExport($request->toDTO()->toFilters()),
            $fileName,
        );
    }

    public function employeesWithLocations(GetProjectNotificationEmployeesRequest $request): JsonResponse
    {
        $employees = $this->locationService->getProjectEmployeesWithLocations(
            $request->input('project_id'),
            (float) $request->input('latitude'),
            (float) $request->input('longitude'),
            $request->filled('radius') ? (float) $request->input('radius') : null,
        );

        return Json::items(
            ProjectNotificationEmployeeLocationPresenter::collection($employees),
        );
    }

    public function approve(Request $request): JsonResponse
    {
        $notification = $this->notificationService->approve(
            $request->route('id'),
            (string) $request->user()->id,
            $request->input('internal_procedure_setting_id'),
        );

        return Json::item($this->detailWithReadStatus($notification));
    }

    public function reject(Request $request): JsonResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
            'internal_procedure_setting_id' => ['nullable', 'string'],
        ]);

        $notification = $this->notificationService->reject(
            $request->route('id'),
            (string) $request->user()->id,
            $request->input('reason'),
            $request->input('internal_procedure_setting_id'),
        );

        return Json::item($this->detailWithReadStatus($notification));
    }

    /**
     * POST /projects/notifications/{id}/read-status
     *
     * Mark the notification as read or unread for the current user.
     * Body: { "is_read": true|false }
     */
    public function updateReadStatus(Request $request): JsonResponse
    {
        $request->validate([
            'is_read' => ['required', 'boolean'],
        ]);

        $notification = $this->notificationService->updateReadStatus(
            $request->route('id'),
            (string) Auth::id(),
            $request->boolean('is_read'),
        );

        $this->notificationService->attachReadStatus([$notification], (string) Auth::id());

        return Json::item(
            $this->detailWithReadStatus($notification),
            message: 'Read status updated successfully',
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Mobile endpoints
    // ─────────────────────────────────────────────────────────────────────────

    public function myTasks(FilterProjectNotificationsRequest $request): JsonResponse
    {
        $paginator = $this->notificationService->myTasks(
            $request->toDTO(),
            (string) Auth::id(),
        );

        $this->notificationService->attachReadStatus($paginator->items(), (string) Auth::id());

        $paginator->getCollection()->loadMissing([
            'employeeTask.user',
            'employeeTask.sessions',
            'employeeTask.employeeTaskType',
            'employeeTask.currentProcedureStep.actionTakers.user',
            'employeeTask.media',
            'employeeTask.createProjectNotificationTaskProcedureSetting',
            'employeeTask.approvalRequests.media',
            'employeeTask.projectNotification.media',
            'employeeTask.workResumptions.media',
        ]);

        return Json::items(
            ProjectNotificationPresenter::collection($paginator->items(), true),
            paginationSettings: [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
        );
    }

    /**
     * GET /projects/notifications/my-inbox
     *
     * Employee inbox: approved project notifications waiting for confirm-receive.
     */
    public function myInbox(FilterProjectNotificationsRequest $request): JsonResponse
    {
        $paginator = $this->notificationService->myInbox(
            $request->toDTO(),
            (string) Auth::id(),
        );

        $this->notificationService->attachReadStatus($paginator->items(), (string) Auth::id());

        $paginator->getCollection()->loadMissing([
            'employeeTask.user',
            'employeeTask.sessions',
            'employeeTask.employeeTaskType',
            'employeeTask.currentProcedureStep.actionTakers.user',
            'employeeTask.media',
            'employeeTask.createProjectNotificationTaskProcedureSetting',
            'employeeTask.processes.steps',
        ]);

        $userId = (string) Auth::id();
        foreach ($paginator->items() as $notification) {
            $notification->setAttribute(
                'pending_processes',
                $this->notificationService->resolvePendingProcessesForInbox($notification, $userId),
            );
        }

        return Json::items(
            ProjectNotificationPresenter::collection($paginator->items(), true),
            paginationSettings: [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
        );
    }

    /**
     * GET /projects/notifications/my-inbox-counts
     *
     * Status counts for the employee's assigned project notifications.
     */
    public function myInboxCounts(Request $request): JsonResponse
    {
        $counts = $this->notificationService->inboxCounts(
            (string) Auth::id(),
            $request->only(['task_date', 'date_from', 'date_to']),
        );

        return Json::item($counts, message: 'Inbox counts retrieved successfully');
    }

    /**
     * GET /projects/notifications/filters
     *
     * Filter metadata for the mobile filter UI (same format as employee-tasks/filters):
     *   - statuses: key, title_ar, title_en, count
     *   - projects: key, title, count
     *   - duration: key, title_ar, title_en, min_minutes, max_minutes
     */
    public function filters(Request $request): JsonResponse
    {
        $metadata = $this->notificationService->filterMetadata(
            (string) Auth::id(),
            $request->only(['task_date', 'date_from', 'date_to']),
        );

        $statuses = [];
        foreach ($metadata['status_counts'] as $statusValue => $count) {
            try {
                $enum = EmployeeTaskStatus::from($statusValue);
            } catch (\ValueError) {
                continue;
            }
            $statuses[] = [
                'key'      => $statusValue,
                'title_ar' => $enum->label('ar'),
                'title_en' => $enum->label('en'),
                'count'    => (int) $count,
            ];
        }

        $projects = [];
        foreach ($metadata['project_counts'] as $project) {
            $projects[] = [
                'key'   => $project['id'],
                'title' => $project['name'],
                'count' => $project['count'],
            ];
        }

        $duration = [
            'key'         => 'duration_minutes',
            'title_ar'    => 'مدة المهمة',
            'title_en'    => 'Task Duration',
            'min_minutes' => $metadata['duration']['min_hours'] !== null
                ? (int) round($metadata['duration']['min_hours'] * 60)
                : null,
            'max_minutes' => $metadata['duration']['max_hours'] !== null
                ? (int) round($metadata['duration']['max_hours'] * 60)
                : null,
        ];

        return Json::item([
            'statuses' => $statuses,
            'projects' => $projects,
            'duration' => $duration,
        ], message: 'Filter metadata retrieved successfully');
    }

    public function availableActions(Request $request): JsonResponse
    {
        $actions = $this->notificationService->availableActions(
            $request->route('id'),
            (string) Auth::id(),
        );

        return Json::items($actions, message: 'Available actions retrieved successfully');
    }

    /**
     * POST /projects/notifications/{id}/confirm-receive
     *
     * Mobile confirm-receive action. Moves the notification from the employee
     * inbox (approved) to the assigned tasks list (in_progress).
     */
    public function confirmReceive(StartTaskRequest $request): JsonResponse
    {
        $user = Auth::user();
        $user->load(['userProfessionalData.branch.address.country', 'userProfessionalData.attendanceConstraint']);

        $task = $this->notificationService->confirmReceive(
            $request->route('id'),
            new StartTaskDTO(
                latitude: (float) $request->input('latitude'),
                longitude: (float) $request->input('longitude'),
                internalProcedureSettingId: $request->input('internal_procedure_setting_id'),
                notes: $request->input('notes'),
            ),
            $user,
        );

        $task->load(['projectNotification', 'company']);

        return Json::item(EmployeeTaskRequestPresenter::single($task), message: 'Task confirmed successfully');
    }

    public function start(StartTaskRequest $request): JsonResponse
    {
        $user = Auth::user();
        $user->load(['userProfessionalData.branch.address.country', 'userProfessionalData.attendanceConstraint']);

        $task = $this->notificationService->startTask(
            $request->route('id'),
            new StartTaskDTO(
                latitude: (float) $request->input('latitude'),
                longitude: (float) $request->input('longitude'),
                internalProcedureSettingId: $request->input('internal_procedure_setting_id'),
                notes: $request->input('notes'),
            ),
            $user,
        );

        $task->load(['projectNotification', 'company']);

        return Json::item(EmployeeTaskRequestPresenter::single($task), message: 'Task started successfully');
    }

    public function takeAction(Request $request): JsonResponse
    {
        $request->validate([
            'internal_procedure_setting_id' => ['required', 'uuid', 'exists:procedure_settings,id'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $result = $this->notificationService->takeAction(
            $request->route('id'),
            (string) $request->input('internal_procedure_setting_id'),
            (string) Auth::id(),
        );

        return Json::item([
            ...$result,
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
            'notes' => $request->input('notes'),
        ], message: 'Procedure action recorded successfully');
    }

    /**
     * GET /projects/notifications/{id}/site-status-updates
     *
     * Returns all periodic site status updates (التحديث الدوري لحالة الموقع) for a
     * notification, including approved records and pending workflow processes.
     * Each item includes form_data, approval status, date, attachments, and
     * workflow steps with approvers.
     */
    public function siteStatusUpdates(Request $request): JsonResponse
    {
        try {
            $result = $this->notificationService->siteStatusUpdates($request->route('id'));

            return Json::item($result, message: 'Site status updates retrieved successfully');
        } catch (ProjectNotificationException $e) {
            return Json::error($e->getMessage(), $e->getCode() ?: 422);
        }
    }

    /**
     * GET /projects/notifications/{id}/site-status-updates/copied
     *
     * Returns only approved site status updates that were marked as copied.
     * Each item has the same shape as the main site-status-updates endpoint,
     * with `is_copied: true`.
     */
    public function copiedSiteStatusUpdates(Request $request): JsonResponse
    {
        try {
            $result = $this->notificationService->copiedSiteStatusUpdates($request->route('id'));

            return Json::item($result, message: 'Copied site status updates retrieved successfully');
        } catch (ProjectNotificationException $e) {
            return Json::error($e->getMessage(), $e->getCode() ?: 422);
        }
    }

    /**
     * POST /projects/notifications/{id}/site-status-updates/{site_status_update_id}/copy
     *
     * Mark a specific approved site status update as copied (is_copied = true).
     */
    public function copySiteStatusUpdate(Request $request): JsonResponse
    {
        try {
            $update = $this->notificationService->copySiteStatusUpdate(
                $request->route('id'),
                $request->route('site_status_update_id'),
            );

            return Json::item(
                ['id' => $update->id, 'is_copied' => $update->is_copied],
                message: 'Site status update marked as copied successfully',
            );
        } catch (ProjectNotificationException $e) {
            return Json::error($e->getMessage(), $e->getCode() ?: 422);
        }
    }

    /**
     * GET /projects/notifications/{id}/notes
     *
     * Returns all user notes for a notification, newest first, including the
     * creator's branch and the timezone-aware created_at timestamp.
     */
    public function notes(Request $request): JsonResponse
    {
        try {
            $result = $this->notificationService->listNotes($request->route('id'));

            return Json::item($result, message: 'Notes retrieved successfully');
        } catch (ProjectNotificationException $e) {
            return Json::error($e->getMessage(), $e->getCode() ?: 422);
        }
    }

    /**
     * POST /projects/notifications/{id}/notes
     *
     * Add a user note to a notification. Body: { "note": "text" }.
     */
    public function addNote(Request $request): JsonResponse
    {
        $request->validate([
            'note' => ['required', 'string'],
        ]);

        try {
            $note = $this->notificationService->addNote(
                $request->route('id'),
                (string) Auth::id(),
                $request->input('note'),
            );

            return Json::item($note, message: 'Note added successfully');
        } catch (ProjectNotificationException $e) {
            return Json::error($e->getMessage(), $e->getCode() ?: 422);
        }
    }

    /**
     * GET /projects/notifications/{id}/procedures
     *
     * Returns the timeline of all taken (completed) internal procedures for the
     * linked EmployeeTask, ordered by taken_at ascending, plus a summary block.
     * This is a convenience wrapper around GET /employee-tasks/{task_id}/procedures
     * so the mobile app does not need to keep the linked task_id.
     */
    public function procedures(Request $request): JsonResponse
    {
        try {
            $debug = $request->boolean('debug');
            $result = $this->notificationService->procedures($request->route('id'), $debug);

            $payload = [
                'items'   => TaskProcedurePresenter::collection($result['items']),
                'summary' => $result['summary'],
            ];

            if ($debug && isset($result['debug'])) {
                $payload['debug'] = $result['debug'];
            }

            return Json::item($payload, message: 'Procedures retrieved successfully');
        } catch (ProjectNotificationException | EmployeeTaskException $e) {
            return Json::error($e->getMessage(), $e->getCode() ?: 422);
        }
    }

    public function end(EndTaskRequest $request): JsonResponse
    {
        $task = $this->notificationService->endTask(
            $request->route('id'),
            new EndTaskDTO(
                latitude: (float) $request->input('latitude'),
                longitude: (float) $request->input('longitude'),
                notes: $request->input('notes'),
                internalProcedureSettingId: $request->input('internal_procedure_setting_id'),
                files: $request->hasFile('files') ? $request->file('files') : null,
                statusId: $request->input('status_id'),
            ),
            (string) Auth::id(),
        );

        $task->load('company');

        return Json::item(EmployeeTaskRequestPresenter::single($task), message: 'Task ended successfully');
    }

    /**
     * Reassign the linked task to another employee and reset it so the new
     * employee starts a fresh lifecycle on the next confirm-receive.
     */
    public function reassign(ReassignProjectNotificationRequest $request): JsonResponse
    {
        $assignedUserIds = $request->input('assigned_user_ids');

        $notification = $this->notificationService->reassignTask(
            $request->route('id'),
            $assignedUserIds,
            (string) Auth::id(),
        );

        $notification->setAttribute(
            'pending_processes',
            $this->notificationService->resolvePendingProcessesForInbox(
                $notification,
                (string) $assignedUserIds[0],
            ),
        );

        $this->notificationService->attachReadStatus([$notification], (string) Auth::id());

        return Json::item(
            ProjectNotificationPresenter::single($notification),
            message: 'Task reassigned successfully',
        );
    }

    /**
     * Present a single notification detail, ensuring the current user's
     * read/unread state is attached for the frontend row background.
     */
    private function detailWithReadStatus(ProjectNotification $notification): array
    {
        $this->notificationService->attachReadStatus([$notification], (string) Auth::id());

        return ProjectNotificationPresenter::detail($notification);
    }
}
