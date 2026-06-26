<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
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
}
