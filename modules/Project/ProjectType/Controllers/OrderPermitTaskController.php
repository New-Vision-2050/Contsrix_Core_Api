<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Project\ProjectType\Presenters\OrderPermitTaskPresenter;
use Modules\Project\ProjectType\Requests\CreateOrderPermitTaskRequest;
use Modules\Project\ProjectType\Requests\UpdateOrderPermitTaskRequest;
use Modules\Project\ProjectType\Services\OrderPermitTaskService;

class OrderPermitTaskController extends Controller
{
    public function __construct(
        private readonly OrderPermitTaskService $service
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $projectTypeId = (int) $request->query('project_type_id');
        $items = $this->service->list($projectTypeId);

        return Json::items(OrderPermitTaskPresenter::collection($items));
    }

    public function show(int $id): JsonResponse
    {
        $item = $this->service->get($id);

        return Json::item((new OrderPermitTaskPresenter($item))->getData());
    }

    public function store(CreateOrderPermitTaskRequest $request): JsonResponse
    {
        $item = $this->service->create($request->validated());

        return Json::item((new OrderPermitTaskPresenter($item))->getData());
    }

    public function update(UpdateOrderPermitTaskRequest $request, int $id): JsonResponse
    {
        $item = $this->service->update($id, $request->validated());

        return Json::item((new OrderPermitTaskPresenter($item))->getData());
    }

    public function delete(int $id): JsonResponse
    {
        $this->service->delete($id);

        return Json::deleted();
    }
}
