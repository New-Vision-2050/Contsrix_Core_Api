<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Project\ProjectType\Presenters\OrderPermitDepartmentPresenter;
use Modules\Project\ProjectType\Requests\CreateOrderPermitDepartmentRequest;
use Modules\Project\ProjectType\Requests\UpdateOrderPermitDepartmentRequest;
use Modules\Project\ProjectType\Services\OrderPermitDepartmentService;

class OrderPermitDepartmentController extends Controller
{
    public function __construct(
        private readonly OrderPermitDepartmentService $service
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $projectTypeId = (int) $request->query('project_type_id');
        $items = $this->service->list($projectTypeId);

        return Json::items(OrderPermitDepartmentPresenter::collection($items));
    }

    public function show(int $id): JsonResponse
    {
        $item = $this->service->get($id);

        return Json::item((new OrderPermitDepartmentPresenter($item))->getData());
    }

    public function store(CreateOrderPermitDepartmentRequest $request): JsonResponse
    {
        $item = $this->service->create($request->validated());

        return Json::item((new OrderPermitDepartmentPresenter($item))->getData());
    }

    public function update(UpdateOrderPermitDepartmentRequest $request, int $id): JsonResponse
    {
        $item = $this->service->update($id, $request->validated());

        return Json::item((new OrderPermitDepartmentPresenter($item))->getData());
    }

    public function delete(int $id): JsonResponse
    {
        $this->service->delete($id);

        return Json::deleted();
    }
}
