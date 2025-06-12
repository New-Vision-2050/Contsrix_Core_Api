<?php

declare(strict_types=1);

namespace Modules\Shared\MaritalStatus\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Shared\MaritalStatus\Handlers\DeleteMaritalStatusHandler;
use Modules\Shared\MaritalStatus\Handlers\UpdateMaritalStatusHandler;
use Modules\Shared\MaritalStatus\Presenters\MaritalStatusPresenter;
use Modules\Shared\MaritalStatus\Requests\CreateMaritalStatusRequest;
use Modules\Shared\MaritalStatus\Requests\DeleteMaritalStatusRequest;
use Modules\Shared\MaritalStatus\Requests\GetMaritalStatusListRequest;
use Modules\Shared\MaritalStatus\Requests\GetMaritalStatusRequest;
use Modules\Shared\MaritalStatus\Requests\UpdateMaritalStatusRequest;
use Modules\Shared\MaritalStatus\Services\MaritalStatusCRUDService;
use Ramsey\Uuid\Uuid;

class MaritalStatusController extends Controller
{
    public function __construct(
        private MaritalStatusCRUDService $maritalStatusService,
        private UpdateMaritalStatusHandler $updateMaritalStatusHandler,
        private DeleteMaritalStatusHandler $deleteMaritalStatusHandler,
    ) {
    }

    public function index(GetMaritalStatusListRequest $request): JsonResponse
    {
        $list = $this->maritalStatusService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(MaritalStatusPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetMaritalStatusRequest $request): JsonResponse
    {
        $item = $this->maritalStatusService->get(Uuid::fromString($request->route('id')));

        $presenter = new MaritalStatusPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateMaritalStatusRequest $request): JsonResponse
    {
        $createdItem = $this->maritalStatusService->create($request->createCreateMaritalStatusDTO());

        $presenter = new MaritalStatusPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateMaritalStatusRequest $request): JsonResponse
    {
        $command = $request->createUpdateMaritalStatusCommand();
        $this->updateMaritalStatusHandler->handle($command);

        $item = $this->maritalStatusService->get($command->getId());

        $presenter = new MaritalStatusPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteMaritalStatusRequest $request): JsonResponse
    {
        $this->deleteMaritalStatusHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
