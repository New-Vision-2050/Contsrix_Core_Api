<?php

declare(strict_types=1);

namespace Modules\Shared\RightTerminate\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Shared\RightTerminate\Handlers\DeleteRightTerminateHandler;
use Modules\Shared\RightTerminate\Handlers\UpdateRightTerminateHandler;
use Modules\Shared\RightTerminate\Presenters\RightTerminatePresenter;
use Modules\Shared\RightTerminate\Requests\CreateRightTerminateRequest;
use Modules\Shared\RightTerminate\Requests\DeleteRightTerminateRequest;
use Modules\Shared\RightTerminate\Requests\GetRightTerminateListRequest;
use Modules\Shared\RightTerminate\Requests\GetRightTerminateRequest;
use Modules\Shared\RightTerminate\Requests\UpdateRightTerminateRequest;
use Modules\Shared\RightTerminate\Services\RightTerminateCRUDService;
use Ramsey\Uuid\Uuid;

class RightTerminateController extends Controller
{
    public function __construct(
        private RightTerminateCRUDService $rightTerminateService,
        private UpdateRightTerminateHandler $updateRightTerminateHandler,
        private DeleteRightTerminateHandler $deleteRightTerminateHandler,
    ) {
    }

    public function index(GetRightTerminateListRequest $request): JsonResponse
    {
        $list = $this->rightTerminateService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(RightTerminatePresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetRightTerminateRequest $request): JsonResponse
    {
        $item = $this->rightTerminateService->get(Uuid::fromString($request->route('id')));

        $presenter = new RightTerminatePresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateRightTerminateRequest $request): JsonResponse
    {
        $createdItem = $this->rightTerminateService->create($request->createCreateRightTerminateDTO());

        $presenter = new RightTerminatePresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateRightTerminateRequest $request): JsonResponse
    {
        $command = $request->createUpdateRightTerminateCommand();
        $this->updateRightTerminateHandler->handle($command);

        $item = $this->rightTerminateService->get($command->getId());

        $presenter = new RightTerminatePresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteRightTerminateRequest $request): JsonResponse
    {
        $this->deleteRightTerminateHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
