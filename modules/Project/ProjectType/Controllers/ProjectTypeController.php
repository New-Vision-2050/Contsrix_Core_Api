<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Project\ProjectType\Handlers\DeleteProjectTypeHandler;
use Modules\Project\ProjectType\Handlers\UpdateProjectTypeHandler;
use Modules\Project\ProjectType\Presenters\ProjectTypePresenter;
use Modules\Project\ProjectType\Requests\CreateProjectTypeRequest;
use Modules\Project\ProjectType\Requests\DeleteProjectTypeRequest;
use Modules\Project\ProjectType\Requests\GetProjectTypeListRequest;
use Modules\Project\ProjectType\Requests\GetProjectTypeRequest;
use Modules\Project\ProjectType\Requests\UpdateProjectTypeRequest;
use Modules\Project\ProjectType\Services\ProjectTypeCRUDService;
use Modules\Project\ProjectType\Exports\ProjectTypeExport;
use Modules\Project\ProjectType\Requests\ExportProjectTypeRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class ProjectTypeController extends Controller
{
    public function __construct(
        private ProjectTypeCRUDService $projectTypeService,
        private UpdateProjectTypeHandler $updateProjectTypeHandler,
        private DeleteProjectTypeHandler $deleteProjectTypeHandler,
    ) {
    }

    public function index(GetProjectTypeListRequest $request): JsonResponse
    {
        $list = $this->projectTypeService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(ProjectTypePresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetProjectTypeRequest $request): JsonResponse
    {
        $item = $this->projectTypeService->get(Uuid::fromString($request->route('id')));

        $presenter = new ProjectTypePresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateProjectTypeRequest $request): JsonResponse
    {
        $createdItem = $this->projectTypeService->create($request->createCreateProjectTypeDTO());

        $presenter = new ProjectTypePresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateProjectTypeRequest $request): JsonResponse
    {
        $command = $request->createUpdateProjectTypeCommand();
        $this->updateProjectTypeHandler->handle($command);

        $item = $this->projectTypeService->get($command->getId());

        $presenter = new ProjectTypePresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteProjectTypeRequest $request): JsonResponse
    {
        $this->deleteProjectTypeHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    /**
     * Export projecttype to a file
     *
     * @param ExportProjectTypeRequest $request
     */
    public function export(ExportProjectTypeRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'project_type.' . $format;
        $filters = $request->getFilters();

        return Excel::download(new ProjectTypeExport($this->projectTypeService, $filters), $fileName);
    }
}
