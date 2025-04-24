<?php

declare(strict_types=1);

namespace Modules\Shared\TimeZone\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Shared\TimeZone\Handlers\DeleteTimeZoneHandler;
use Modules\Shared\TimeZone\Handlers\UpdateTimeZoneHandler;
use Modules\Shared\TimeZone\Presenters\TimeZonePresenter;
use Modules\Shared\TimeZone\Requests\CreateTimeZoneRequest;
use Modules\Shared\TimeZone\Requests\DeleteTimeZoneRequest;
use Modules\Shared\TimeZone\Requests\GetTimeZoneListRequest;
use Modules\Shared\TimeZone\Requests\GetTimeZoneRequest;
use Modules\Shared\TimeZone\Requests\UpdateTimeZoneRequest;
use Modules\Shared\TimeZone\Services\TimeZoneCRUDService;
use Ramsey\Uuid\Uuid;

class TimeZoneController extends Controller
{
    public function __construct(
        private TimeZoneCRUDService $timeZoneService,
        private UpdateTimeZoneHandler $updateTimeZoneHandler,
        private DeleteTimeZoneHandler $deleteTimeZoneHandler,
    ) {
    }

    public function index(GetTimeZoneListRequest $request): JsonResponse
    {
        $list = $this->timeZoneService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(TimeZonePresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetTimeZoneRequest $request): JsonResponse
    {
        $item = $this->timeZoneService->get(Uuid::fromString($request->route('id')));

        $presenter = new TimeZonePresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateTimeZoneRequest $request): JsonResponse
    {
        $createdItem = $this->timeZoneService->create($request->createCreateTimeZoneDTO());

        $presenter = new TimeZonePresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateTimeZoneRequest $request): JsonResponse
    {
        $command = $request->createUpdateTimeZoneCommand();
        $this->updateTimeZoneHandler->handle($command);

        $item = $this->timeZoneService->get($command->getId());

        $presenter = new TimeZonePresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteTimeZoneRequest $request): JsonResponse
    {
        $this->deleteTimeZoneHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
