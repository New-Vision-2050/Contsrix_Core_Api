<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Project\ProjectManagement\Handlers\DeleteProjectManagementHandler;
use Modules\Project\ProjectManagement\Handlers\UpdateProjectManagementHandler;
use Modules\Project\ProjectManagement\Models\ContractualEngagement;
use Modules\Project\ProjectManagement\Presenters\ProjectManagementPresenter;
use Modules\Project\ProjectManagement\Requests\CreateProjectManagementRequest;
use Modules\Project\ProjectManagement\Requests\DeleteProjectManagementRequest;
use Modules\Project\ProjectManagement\Requests\GetProjectManagementListRequest;
use Modules\Project\ProjectManagement\Requests\GetProjectManagementRequest;
use Modules\Project\ProjectManagement\Requests\UpdateProjectManagementRequest;
use Modules\Project\ProjectManagement\Requests\UploadProjectStampRequest;
use Modules\Project\ProjectManagement\Services\ProjectManagementCRUDService;
use Modules\Project\ProjectManagement\Services\ProjectManagementDashboardWidgetsService;
use Modules\Project\ProjectManagement\Presenters\ProjectManagementDashboardWidgetsPresenter;
use Modules\Project\ProjectManagement\Exports\ProjectManagementExport;
use Modules\Project\ProjectManagement\Requests\ExportProjectManagementRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;
use Illuminate\Http\Request;

class ProjectManagementController extends Controller
{
    public function __construct(
        private ProjectManagementCRUDService $projectManagementService,
        private ProjectManagementDashboardWidgetsService $dashboardWidgetsService,
        private UpdateProjectManagementHandler $updateProjectManagementHandler,
        private DeleteProjectManagementHandler $deleteProjectManagementHandler,
    ) {
    }

    public function index(GetProjectManagementListRequest $request): JsonResponse
    {
        $list = $this->projectManagementService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10),
            $request->user(),
            $request->only([
                'name',
                'project_type_id',
                'sub_project_type_id',
                'sub_sub_project_type_id',
                'manager_id',
                'branch_id',
                'project_owner_type',
                'project_owner_id',
                'contract_id',
                'client_id',
                'management_id',
                'status',
            ]),
        );

        return Json::items(ProjectManagementPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetProjectManagementRequest $request): JsonResponse
    {
        $item = $this->projectManagementService->get(Uuid::fromString($request->route('id')));

        $presenter = new ProjectManagementPresenter($item);

        return Json::item($presenter->getData());
    }

    public function showStamp(GetProjectManagementRequest $request): JsonResponse
    {
        $item = $this->projectManagementService->get(Uuid::fromString($request->route('id')));

        return Json::item([
            'stamp' => $item->stampUrl(),
        ]);
    }

    public function storeStamp(UploadProjectStampRequest $request): JsonResponse
    {
        $item = $this->projectManagementService->uploadStamp(
            Uuid::fromString($request->route('id')),
            $request->file('stamp'),
        );

        return Json::item([
            'stamp' => $item->stampUrl(),
        ]);
    }

    public function store(CreateProjectManagementRequest $request): JsonResponse
    {
        $createdItem = $this->projectManagementService->create($request->createCreateProjectManagementDTO());

        $presenter = new ProjectManagementPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateProjectManagementRequest $request): JsonResponse
    {
        $command = $request->createUpdateProjectManagementCommand();
        $this->updateProjectManagementHandler->handle($command);

        $item = $this->projectManagementService->get($command->getId());

        $presenter = new ProjectManagementPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteProjectManagementRequest $request): JsonResponse
    {
        $this->deleteProjectManagementHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    /**
     * Export projectmanagement to a file
     *
     * @param ExportProjectManagementRequest $request
     */
    public function export(ExportProjectManagementRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'project_management.' . $format;
        $filters = $request->getFilters();

        return Excel::download(new ProjectManagementExport($this->projectManagementService, $filters), $fileName);
    }

    /**
     * Get project dashboard widgets data
     */
    public function widgets(Request $request): JsonResponse
    {
        $companyId = tenant('id');
        $dateRange = [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
        ];

        $widgetsData = $this->dashboardWidgetsService->getWidgetsData($companyId, $dateRange);

        $presentedData = ProjectManagementDashboardWidgetsPresenter::presentWidgets($widgetsData);

        return Json::items($presentedData);
    }

    /**
     * List active contractual engagements for dropdown selection.
     */
    public function contractualEngagements(): JsonResponse
    {
        $engagements = ContractualEngagement::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name_ar', 'name_en', 'code', 'sort_order']);

        $items = $engagements->map(fn (ContractualEngagement $engagement) => [
            'id' => $engagement->id,
            'name_ar' => $engagement->name_ar,
            'name_en' => $engagement->name_en,
            'code' => $engagement->code,
            'sort_order' => $engagement->sort_order,
        ])->toArray();

        return Json::items($items);
    }

    /**
     * List active project tags for dropdown selection.
     */
    public function projectTags(): JsonResponse
    {
        return Json::items($this->projectManagementService->projectTags());
    }
}
