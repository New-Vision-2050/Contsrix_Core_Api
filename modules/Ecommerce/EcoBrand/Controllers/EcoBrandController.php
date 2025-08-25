<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBrand\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\EcoBrand\Handlers\DeleteEcoBrandHandler;
use Modules\Ecommerce\EcoBrand\Handlers\UpdateEcoBrandHandler;
use Modules\Ecommerce\EcoBrand\Presenters\EcoBrandPresenter;
use Modules\Ecommerce\EcoBrand\Requests\CreateEcoBrandRequest;
use Modules\Ecommerce\EcoBrand\Requests\DeleteEcoBrandRequest;
use Modules\Ecommerce\EcoBrand\Requests\GetEcoBrandListRequest;
use Modules\Ecommerce\EcoBrand\Requests\GetEcoBrandRequest;
use Modules\Ecommerce\EcoBrand\Requests\UpdateEcoBrandRequest;
use Modules\Ecommerce\EcoBrand\Services\EcoBrandCRUDService;
use Ramsey\Uuid\Uuid;

class EcoBrandController extends Controller
{
    public function __construct(
        private EcoBrandCRUDService $ecoBrandService,
        private UpdateEcoBrandHandler $updateEcoBrandHandler,
        private DeleteEcoBrandHandler $deleteEcoBrandHandler,
    ) {
    }

    public function index(GetEcoBrandListRequest $request): JsonResponse
    {
        $list = $this->ecoBrandService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(EcoBrandPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetEcoBrandRequest $request): JsonResponse
    {
        $item = $this->ecoBrandService->get(Uuid::fromString($request->route('id')));

        $presenter = new EcoBrandPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateEcoBrandRequest $request): JsonResponse
    {
        $createdItem = $this->ecoBrandService->create($request->createCreateEcoBrandDTO());

        $presenter = new EcoBrandPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateEcoBrandRequest $request): JsonResponse
    {
        $command = $request->createUpdateEcoBrandCommand();
        $this->updateEcoBrandHandler->handle($command);

        $item = $this->ecoBrandService->get($command->getId());

        $presenter = new EcoBrandPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteEcoBrandRequest $request): JsonResponse
    {
        $this->deleteEcoBrandHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
