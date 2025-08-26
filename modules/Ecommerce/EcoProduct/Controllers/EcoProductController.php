<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\EcoProduct\Handlers\DeleteEcoProductHandler;
use Modules\Ecommerce\EcoProduct\Handlers\UpdateEcoProductHandler;
use Modules\Ecommerce\EcoProduct\Presenters\EcoProductPresenter;
use Modules\Ecommerce\EcoProduct\Requests\CreateEcoProductRequest;
use Modules\Ecommerce\EcoProduct\Requests\DeleteEcoProductRequest;
use Modules\Ecommerce\EcoProduct\Requests\GetEcoProductListRequest;
use Modules\Ecommerce\EcoProduct\Requests\GetEcoProductRequest;
use Modules\Ecommerce\EcoProduct\Requests\UpdateEcoProductRequest;
use Modules\Ecommerce\EcoProduct\Services\EcoProductCRUDService;
use Ramsey\Uuid\Uuid;

class EcoProductController extends Controller
{
    public function __construct(
        private EcoProductCRUDService $ecoProductService,
        private UpdateEcoProductHandler $updateEcoProductHandler,
        private DeleteEcoProductHandler $deleteEcoProductHandler,
    ) {
    }

    public function index(GetEcoProductListRequest $request): JsonResponse
    {
        $list = $this->ecoProductService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(EcoProductPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetEcoProductRequest $request): JsonResponse
    {
        $item = $this->ecoProductService->get(Uuid::fromString($request->route('id')));

        $presenter = new EcoProductPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateEcoProductRequest $request): JsonResponse
    {
        $createdItem = $this->ecoProductService->create($request->createCreateEcoProductDTO());

        $presenter = new EcoProductPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateEcoProductRequest $request): JsonResponse
    {
        $command = $request->createUpdateEcoProductCommand();
        $this->updateEcoProductHandler->handle($command);

        $item = $this->ecoProductService->get($command->getId());

        $presenter = new EcoProductPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteEcoProductRequest $request): JsonResponse
    {
        $this->deleteEcoProductHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
