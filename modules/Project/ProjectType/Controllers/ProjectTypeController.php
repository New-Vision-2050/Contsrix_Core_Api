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
use Modules\Project\ProjectType\Requests\CreateSecondLevelProjectTypeRequest;
use Modules\Project\ProjectType\Requests\DeleteProjectTypeRequest;
use Modules\Project\ProjectType\Requests\GetProjectTypeListRequest;
use Modules\Project\ProjectType\Requests\GetProjectTypeRequest;
use Modules\Project\ProjectType\Requests\UpdateProjectTypeRequest;
use Modules\Project\ProjectType\Services\ProjectTypeCRUDService;
use Modules\Project\ProjectType\Exports\ProjectTypeExport;
use Modules\Project\ProjectType\Requests\ExportProjectTypeRequest;
use Modules\Project\ProjectType\Presenters\SchemaPresenter;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

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
        $item = $this->projectTypeService->get((int) $request->route('id'));

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
        $this->deleteProjectTypeHandler->handle((int) $request->route('id'));

        return Json::deleted();
    }

    public function getDirectChildren(GetProjectTypeRequest $request): JsonResponse
    {
        $parentId = (int) $request->route('id');
        $children = $this->projectTypeService->getDirectChildren($parentId);

        return Json::items(ProjectTypePresenter::collection($children));
    }

    public function getRootProjectTypes(): JsonResponse
    {
        $roots = $this->projectTypeService->getRootProjectTypes();

        return Json::items(ProjectTypePresenter::collection($roots));
    }

    public function createSecondLevel(CreateSecondLevelProjectTypeRequest $request): JsonResponse
    {
        $createdItem = $this->projectTypeService->createSecondLevelProjectType($request->createDTO());

        $presenter = new ProjectTypePresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function getSchemas(GetProjectTypeRequest $request): JsonResponse
    {
        $projectTypeId = (int) $request->route('id');

        // Get the project type with children
        $projectType = $this->projectTypeService->getProjectTypeWithChildren($projectTypeId);

        // Check if project type has children
        if ($projectType->children->isEmpty()) {
            return Json::items([]);
        }

        // Check if project type is at second level (has parent but parent has no parent)
        $isSecondLevel = $projectType->parent_id && $projectType->parent && is_null($projectType->parent->parent_id);

        if (!$isSecondLevel) {
            return Json::items([]);
        }

        // Get schemas only if has children and is at second level
        $schemas = $this->projectTypeService->getSchemasForProjectType($projectTypeId);

        return Json::items(SchemaPresenter::collection($schemas));
    }


    public function getSecondLevelProjectTypeSchemas(GetProjectTypeRequest $request): JsonResponse
    {
        $projectTypeId = (int) $request->route('id');

        // Get the project type with children
        $projectType = $this->projectTypeService->getProjectTypeWithChildren($projectTypeId);


        // Check if project type is at second level (has parent but parent has no parent)
        $isSecondLevel = $projectType->parent_id && $projectType->parent && is_null($projectType->parent->parent_id);

        if (!$isSecondLevel) {
            return Json::items([]);
        }

        // Get schemas only if has children and is at second level
        $schemas = $this->projectTypeService->getSchemasForProjectType($projectTypeId);

        return Json::items(SchemaPresenter::collection($schemas));
    }

    public function getByFilter(Request $request): JsonResponse
    {
        $filters = [
            'second_level' => $request->boolean('second_level'),
            'parent_id' => $request->get('parent_id'),
            'is_have_schema' => $request->has('is_have_schema') ? $request->boolean('is_have_schema') : null,
            'is_created' => $request->has('is_created') ? $request->boolean('is_created') : null,
        ];

        $projectTypes = $this->projectTypeService->getProjectTypesByFilter($filters);

        return Json::items(ProjectTypePresenter::collection($projectTypes));
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
