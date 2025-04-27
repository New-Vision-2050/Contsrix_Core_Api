<?php

declare(strict_types=1);

namespace Modules\Shared\TypeWorkingHour\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Shared\TypeWorkingHour\Handlers\DeleteTypeWorkingHourHandler;
use Modules\Shared\TypeWorkingHour\Handlers\UpdateTypeWorkingHourHandler;
use Modules\Shared\TypeWorkingHour\Presenters\TypeWorkingHourPresenter;
use Modules\Shared\TypeWorkingHour\Requests\CreateTypeWorkingHourRequest;
use Modules\Shared\TypeWorkingHour\Requests\DeleteTypeWorkingHourRequest;
use Modules\Shared\TypeWorkingHour\Requests\GetTypeWorkingHourListRequest;
use Modules\Shared\TypeWorkingHour\Requests\GetTypeWorkingHourRequest;
use Modules\Shared\TypeWorkingHour\Requests\UpdateTypeWorkingHourRequest;
use Modules\Shared\TypeWorkingHour\Services\TypeWorkingHourCRUDService;
use Ramsey\Uuid\Uuid;

class TypeWorkingHourController extends Controller
{
    public function __construct(
        private TypeWorkingHourCRUDService $typeWorkingHourService,
        private UpdateTypeWorkingHourHandler $updateTypeWorkingHourHandler,
        private DeleteTypeWorkingHourHandler $deleteTypeWorkingHourHandler,
    ) {
    }

    public function index(GetTypeWorkingHourListRequest $request): JsonResponse
    {
        $list = $this->typeWorkingHourService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(TypeWorkingHourPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetTypeWorkingHourRequest $request): JsonResponse
    {
        $item = $this->typeWorkingHourService->get(Uuid::fromString($request->route('id')));

        $presenter = new TypeWorkingHourPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateTypeWorkingHourRequest $request): JsonResponse
    {
        $createdItem = $this->typeWorkingHourService->create($request->createCreateTypeWorkingHourDTO());

        $presenter = new TypeWorkingHourPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateTypeWorkingHourRequest $request): JsonResponse
    {
        $command = $request->createUpdateTypeWorkingHourCommand();
        $this->updateTypeWorkingHourHandler->handle($command);

        $item = $this->typeWorkingHourService->get($command->getId());

        $presenter = new TypeWorkingHourPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteTypeWorkingHourRequest $request): JsonResponse
    {
        $this->deleteTypeWorkingHourHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
