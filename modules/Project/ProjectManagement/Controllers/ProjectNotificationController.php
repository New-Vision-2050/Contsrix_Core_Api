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
use Modules\EmployeeTask\Presenters\EmployeeTaskRequestPresenter;
use Modules\EmployeeTask\Requests\EndTaskRequest;
use Modules\EmployeeTask\Requests\StartTaskRequest;
use Modules\Project\ProjectManagement\Exports\ProjectNotificationExport;
use Modules\Project\ProjectManagement\Presenters\ProjectNotificationEmployeeLocationPresenter;
use Modules\Project\ProjectManagement\Presenters\ProjectNotificationPresenter;
use Modules\Project\ProjectManagement\Requests\CreateProjectNotificationRequest;
use Modules\Project\ProjectManagement\Requests\FilterProjectNotificationsRequest;
use Modules\Project\ProjectManagement\Requests\GetProjectNotificationEmployeesRequest;
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
        );

        return Json::item(ProjectNotificationPresenter::detail($notification));
    }

    public function reject(Request $request): JsonResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $notification = $this->notificationService->reject(
            $request->route('id'),
            $request->user()->id,
            $request->input('reason'),
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

    public function availableActions(Request $request): JsonResponse
    {
        $actions = $this->notificationService->availableActions($request->route('id'));

        return Json::items($actions, message: 'Available actions retrieved successfully');
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
