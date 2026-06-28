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
use Modules\Project\ProjectManagement\Presenters\ProjectNotificationEmployeeLocationPresenter;
use Modules\Project\ProjectManagement\Presenters\ProjectNotificationPresenter;
use Modules\Project\ProjectManagement\Requests\CreateProjectNotificationRequest;
use Modules\Project\ProjectManagement\Requests\FilterProjectNotificationsRequest;
use Modules\Project\ProjectManagement\Requests\GetProjectNotificationEmployeesRequest;
use Modules\Project\ProjectManagement\Requests\RequestProjectNotificationFineRequest;
use Modules\Project\ProjectManagement\Requests\RequestProjectNotificationLocationConfirmationRequest;
use Modules\Project\ProjectManagement\Requests\RequestProjectNotificationSiteStatusUpdateRequest;
use Modules\Project\ProjectManagement\Requests\RequestProjectNotificationUpdateRequest;
use Modules\Project\ProjectManagement\Requests\RequestProjectNotificationWorkStoppageReportRequest;
use Modules\Project\ProjectManagement\Requests\UpdateProjectNotificationRequest;
use Modules\Project\ProjectManagement\Services\ProjectNotificationLocationService;
use Modules\Project\ProjectManagement\Services\ProjectNotificationService;

class ProjectNotificationController extends Controller
{
    public function __construct(
        private readonly ProjectNotificationService $notificationService,
        private readonly ProjectNotificationLocationService $locationService,
    ) {}

    public function index(FilterProjectNotificationsRequest $request): JsonResponse
    {
        $paginator = $this->notificationService->list($request->toDTO());

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

        return Json::item(
            ProjectNotificationPresenter::detail($notification)
        );
    }

    public function show(Request $request): JsonResponse
    {
        $notification = $this->notificationService->get($request->route('id'));

        return Json::item(ProjectNotificationPresenter::detail($notification));
    }

    public function update(UpdateProjectNotificationRequest $request): JsonResponse
    {
        $notification = $this->notificationService->update(
            $request->route('id'),
            $request->toDTO(),
        );

        return Json::item(ProjectNotificationPresenter::detail($notification));
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
            ProjectNotificationPresenter::detail($notification),
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
            ProjectNotificationPresenter::detail($notification),
            message: 'Site status update request submitted successfully',
        );
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
            ProjectNotificationPresenter::detail($notification),
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
            ProjectNotificationPresenter::detail($notification),
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
            ProjectNotificationPresenter::detail($notification),
            message: 'Work stoppage report request submitted successfully',
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
            $request->user()->id,
            $request->input('procedure_setting_id'),
        );

        return Json::item(ProjectNotificationPresenter::detail($notification));
    }

    public function reject(Request $request): JsonResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
            'procedure_setting_id' => ['nullable', 'string'],
        ]);

        $notification = $this->notificationService->reject(
            $request->route('id'),
            $request->user()->id,
            $request->input('reason'),
            $request->input('procedure_setting_id'),
        );

        return Json::item(ProjectNotificationPresenter::detail($notification));
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

        $paginator->getCollection()->loadMissing([
            'assignedUser',
            'employeeTask.user',
            'employeeTask.sessions',
            'employeeTask.employeeTaskType',
            'employeeTask.currentProcedureStep.actionTakers.user',
            'employeeTask.media',
            'employeeTask.createProjectNotificationTaskProcedureSetting',
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

        $paginator->getCollection()->loadMissing([
            'assignedUser',
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
        $actions = $this->notificationService->availableActions($request->route('id'));

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
            ),
        );

        return Json::item(EmployeeTaskRequestPresenter::single($task), message: 'Task ended successfully');
    }
}
