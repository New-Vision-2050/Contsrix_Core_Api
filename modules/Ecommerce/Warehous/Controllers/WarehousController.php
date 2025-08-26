<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Warehous\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\Warehous\Handlers\DeleteWarehousHandler;
use Modules\Ecommerce\Warehous\Handlers\UpdateWarehousHandler;
use Modules\Ecommerce\Warehous\Presenters\WarehousPresenter;
use Modules\Ecommerce\Warehous\Requests\CreateWarehousRequest;
use Modules\Ecommerce\Warehous\Requests\DeleteWarehousRequest;
use Modules\Ecommerce\Warehous\Requests\GetWarehousListRequest;
use Modules\Ecommerce\Warehous\Requests\GetWarehousRequest;
use Modules\Ecommerce\Warehous\Requests\UpdateWarehousRequest;
use Modules\Ecommerce\Warehous\Services\WarehousCRUDService;
use Ramsey\Uuid\Uuid;

class WarehousController extends Controller
{
    public function __construct(
        private WarehousCRUDService $warehousService,
        private UpdateWarehousHandler $updateWarehousHandler,
        private DeleteWarehousHandler $deleteWarehousHandler,
    ) {
    }

    public function index(GetWarehousListRequest $request): JsonResponse
    {
        $list = $this->warehousService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(WarehousPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetWarehousRequest $request): JsonResponse
    {
        $item = $this->warehousService->get(Uuid::fromString($request->route('id')));

        $presenter = new WarehousPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateWarehousRequest $request): JsonResponse
    {
        $createdItem = $this->warehousService->create($request->createCreateWarehousDTO());

        $presenter = new WarehousPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateWarehousRequest $request): JsonResponse
    {
        $command = $request->createUpdateWarehousCommand();
        $this->updateWarehousHandler->handle($command);

        $item = $this->warehousService->get($command->getId());

        $presenter = new WarehousPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteWarehousRequest $request): JsonResponse
    {
        $this->deleteWarehousHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
