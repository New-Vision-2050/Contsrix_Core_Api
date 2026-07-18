<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Project\ProjectType\Presenters\ProjectManagementPresenter;
use Modules\Project\ProjectType\Requests\CreateProjectManagementRequest;
use Modules\Project\ProjectType\Requests\UpdateProjectManagementRequest;
use Modules\Project\ProjectType\Services\ProjectManagementService;

class ProjectManagementController extends Controller
{
    public function __construct(
        private readonly ProjectManagementService $service
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $items = $this->service->list();

        return Json::items(ProjectManagementPresenter::collection($items));
    }

    public function show(int $id): JsonResponse
    {
        $item = $this->service->get($id);

        return Json::item((new ProjectManagementPresenter($item))->getData());
    }

    public function store(CreateProjectManagementRequest $request): JsonResponse
    {
        $item = $this->service->create($request->validated());

        return Json::item((new ProjectManagementPresenter($item))->getData());
    }

    public function update(UpdateProjectManagementRequest $request, int $id): JsonResponse
    {
        $item = $this->service->update($id, $request->validated());

        return Json::item((new ProjectManagementPresenter($item))->getData());
    }

    public function delete(int $id): JsonResponse
    {
        $this->service->delete($id);

        return Json::deleted();
    }
}
