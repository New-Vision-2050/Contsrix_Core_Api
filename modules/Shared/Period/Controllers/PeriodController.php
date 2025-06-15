<?php

declare(strict_types=1);

namespace Modules\Shared\Period\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Shared\Period\Handlers\DeletePeriodHandler;
use Modules\Shared\Period\Handlers\UpdatePeriodHandler;
use Modules\Shared\Period\Presenters\PeriodPresenter;
use Modules\Shared\Period\Requests\CreatePeriodRequest;
use Modules\Shared\Period\Requests\DeletePeriodRequest;
use Modules\Shared\Period\Requests\GetPeriodListRequest;
use Modules\Shared\Period\Requests\GetPeriodRequest;
use Modules\Shared\Period\Requests\UpdatePeriodRequest;
use Modules\Shared\Period\Services\PeriodCRUDService;
use Ramsey\Uuid\Uuid;

class PeriodController extends Controller
{
    public function __construct(
        private PeriodCRUDService $periodService,
        private UpdatePeriodHandler $updatePeriodHandler,
        private DeletePeriodHandler $deletePeriodHandler,
    ) {
    }

    public function index(GetPeriodListRequest $request): JsonResponse
    {
        $list = $this->periodService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(PeriodPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetPeriodRequest $request): JsonResponse
    {
        $item = $this->periodService->get(Uuid::fromString($request->route('id')));

        $presenter = new PeriodPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreatePeriodRequest $request): JsonResponse
    {
        $createdItem = $this->periodService->create($request->createCreatePeriodDTO());

        $presenter = new PeriodPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdatePeriodRequest $request): JsonResponse
    {
        $command = $request->createUpdatePeriodCommand();
        $this->updatePeriodHandler->handle($command);

        $item = $this->periodService->get($command->getId());

        $presenter = new PeriodPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeletePeriodRequest $request): JsonResponse
    {
        $this->deletePeriodHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
