<?php

declare(strict_types=1);

namespace Modules\Leave\LeavePolicyCategory\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Leave\LeavePolicyCategory\Handlers\DeleteLeavePolicyCategoryHandler;
use Modules\Leave\LeavePolicyCategory\Handlers\UpdateLeavePolicyCategoryHandler;
use Modules\Leave\LeavePolicyCategory\Presenters\LeavePolicyCategoryPresenter;
use Modules\Leave\LeavePolicyCategory\Requests\CreateLeavePolicyCategoryRequest;
use Modules\Leave\LeavePolicyCategory\Requests\DeleteLeavePolicyCategoryRequest;
use Modules\Leave\LeavePolicyCategory\Requests\GetLeavePolicyCategoryListRequest;
use Modules\Leave\LeavePolicyCategory\Requests\GetLeavePolicyCategoryRequest;
use Modules\Leave\LeavePolicyCategory\Requests\UpdateLeavePolicyCategoryRequest;
use Modules\Leave\LeavePolicyCategory\Services\LeavePolicyCategoryCRUDService;
use Ramsey\Uuid\Uuid;

class LeavePolicyCategoryController extends Controller
{
    public function __construct(
        private LeavePolicyCategoryCRUDService $service,
        private UpdateLeavePolicyCategoryHandler $updateHandler,
        private DeleteLeavePolicyCategoryHandler $deleteHandler,
    ) {
    }

    public function index(GetLeavePolicyCategoryListRequest $request): JsonResponse
    {
        $list = $this->service->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(LeavePolicyCategoryPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetLeavePolicyCategoryRequest $request): JsonResponse
    {
        $item = $this->service->get(Uuid::fromString($request->route('id')));

        return Json::item((new LeavePolicyCategoryPresenter($item))->getData());
    }

    public function store(CreateLeavePolicyCategoryRequest $request): JsonResponse
    {
        $item = $this->service->create($request->createCreateLeavePolicyCategoryDTO());

        return Json::item((new LeavePolicyCategoryPresenter($item))->getData());
    }

    public function update(UpdateLeavePolicyCategoryRequest $request): JsonResponse
    {
        $command = $request->createUpdateLeavePolicyCategoryCommand();
        $this->updateHandler->handle($command);

        $item = $this->service->get($command->getId());

        return Json::item((new LeavePolicyCategoryPresenter($item))->getData());
    }

    public function delete(DeleteLeavePolicyCategoryRequest $request): JsonResponse
    {
        $this->deleteHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
