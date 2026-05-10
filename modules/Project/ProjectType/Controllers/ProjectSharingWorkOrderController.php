<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Project\ProjectType\Presenters\ProjectSharingWorkOrderPresenter;
use Modules\Project\ProjectType\Requests\CreateProjectSharingWorkOrderRequest;
use Modules\Project\ProjectType\Requests\UpdateProjectSharingWorkOrderRequest;
use Modules\Project\ProjectType\Services\ProjectSharingWorkOrderService;

class ProjectSharingWorkOrderController extends Controller
{
    public function __construct(
        private readonly ProjectSharingWorkOrderService $service
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $projectTypeId = (int) $request->query('project_type_id');
        $items = $this->service->list($projectTypeId);

        return Json::items(ProjectSharingWorkOrderPresenter::collection($items));
    }

    public function show(int $id): JsonResponse
    {
        $item = $this->service->get($id);

        return Json::item((new ProjectSharingWorkOrderPresenter($item))->getData());
    }

    public function store(CreateProjectSharingWorkOrderRequest $request): JsonResponse
    {
        $item = $this->service->create($request->validated());

        return Json::item((new ProjectSharingWorkOrderPresenter($item))->getData());
    }

    public function update(UpdateProjectSharingWorkOrderRequest $request, int $id): JsonResponse
    {
        $item = $this->service->update($id, $request->validated());

        return Json::item((new ProjectSharingWorkOrderPresenter($item))->getData());
    }

    public function delete(int $id): JsonResponse
    {
        $this->service->delete($id);

        return Json::deleted();
    }
}
