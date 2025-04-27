<?php

declare(strict_types=1);

namespace Modules\Shared\TimeUnit\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Shared\TimeUnit\Handlers\DeleteTimeUnitHandler;
use Modules\Shared\TimeUnit\Handlers\UpdateTimeUnitHandler;
use Modules\Shared\TimeUnit\Presenters\TimeUnitPresenter;
use Modules\Shared\TimeUnit\Requests\CreateTimeUnitRequest;
use Modules\Shared\TimeUnit\Requests\DeleteTimeUnitRequest;
use Modules\Shared\TimeUnit\Requests\GetTimeUnitListRequest;
use Modules\Shared\TimeUnit\Requests\GetTimeUnitRequest;
use Modules\Shared\TimeUnit\Requests\UpdateTimeUnitRequest;
use Modules\Shared\TimeUnit\Services\TimeUnitCRUDService;
use Ramsey\Uuid\Uuid;

class TimeUnitController extends Controller
{
    public function __construct(
        private TimeUnitCRUDService $timeUnitService,
        private UpdateTimeUnitHandler $updateTimeUnitHandler,
        private DeleteTimeUnitHandler $deleteTimeUnitHandler,
    ) {
    }

    public function index(GetTimeUnitListRequest $request): JsonResponse
    {
        $list = $this->timeUnitService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(TimeUnitPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetTimeUnitRequest $request): JsonResponse
    {
        $item = $this->timeUnitService->get(Uuid::fromString($request->route('id')));

        $presenter = new TimeUnitPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateTimeUnitRequest $request): JsonResponse
    {
        $createdItem = $this->timeUnitService->create($request->createCreateTimeUnitDTO());

        $presenter = new TimeUnitPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateTimeUnitRequest $request): JsonResponse
    {
        $command = $request->createUpdateTimeUnitCommand();
        $this->updateTimeUnitHandler->handle($command);

        $item = $this->timeUnitService->get($command->getId());

        $presenter = new TimeUnitPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteTimeUnitRequest $request): JsonResponse
    {
        $this->deleteTimeUnitHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
