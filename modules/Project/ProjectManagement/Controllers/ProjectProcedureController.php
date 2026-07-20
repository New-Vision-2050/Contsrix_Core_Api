<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Modules\Project\ProjectManagement\Presenters\ProjectProcedurePresenter;
use Modules\Project\ProjectManagement\Requests\StoreProjectProcedureRequest;
use Modules\Project\ProjectManagement\Requests\UpdateProjectProcedureRequest;
use Modules\Project\ProjectManagement\Services\ProjectProcedureService;

class ProjectProcedureController extends Controller
{
    public function __construct(private readonly ProjectProcedureService $service) {}

    public function index(string $project): JsonResponse
    {
        $items = $this->service->list($project);

        return Json::items(ProjectProcedurePresenter::collection($items));
    }

    public function store(StoreProjectProcedureRequest $request, string $project): JsonResponse
    {
        $item = $this->service->create(
            $project,
            $request->procedureData(),
            $request->metadataData(),
        );

        return Json::item((new ProjectProcedurePresenter($item))->getData());
    }

    public function show(string $project, string $procedure): JsonResponse
    {
        $item = $this->service->get($project, $procedure);

        return Json::item((new ProjectProcedurePresenter($item))->getData());
    }

    public function update(UpdateProjectProcedureRequest $request, string $project, string $procedure): JsonResponse
    {
        $item = $this->service->update(
            $project,
            $procedure,
            $request->procedureData(),
            $request->metadataData(),
        );

        return Json::item((new ProjectProcedurePresenter($item))->getData());
    }

    public function destroy(string $project, string $procedure): JsonResponse
    {
        $this->service->delete($project, $procedure);

        return Json::deleted();
    }
}
