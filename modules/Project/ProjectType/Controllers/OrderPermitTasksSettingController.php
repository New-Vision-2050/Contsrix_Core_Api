<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Project\ProjectType\Presenters\OrderPermitTasksSettingPresenter;
use Modules\Project\ProjectType\Requests\CreateOrderPermitTasksSettingRequest;
use Modules\Project\ProjectType\Requests\UpdateOrderPermitTasksSettingRequest;
use Modules\Project\ProjectType\Services\OrderPermitTasksSettingService;

class OrderPermitTasksSettingController extends Controller
{
    public function __construct(
        private readonly OrderPermitTasksSettingService $service
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $projectTypeId = (int) $request->query('project_type_id');
        $items = $this->service->list($projectTypeId);

        return Json::items(OrderPermitTasksSettingPresenter::collection($items));
    }

    public function show(int $id): JsonResponse
    {
        $item = $this->service->get($id);

        return Json::item((new OrderPermitTasksSettingPresenter($item))->getData());
    }

    public function store(CreateOrderPermitTasksSettingRequest $request): JsonResponse
    {
        $item = $this->service->create($request->validated());

        return Json::item((new OrderPermitTasksSettingPresenter($item))->getData());
    }

    public function update(UpdateOrderPermitTasksSettingRequest $request, int $id): JsonResponse
    {
        $item = $this->service->update($id, $request->validated());

        return Json::item((new OrderPermitTasksSettingPresenter($item))->getData());
    }

    public function delete(int $id): JsonResponse
    {
        $this->service->delete($id);

        return Json::deleted();
    }
}
