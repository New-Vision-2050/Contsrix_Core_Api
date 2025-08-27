<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoCategory\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\EcoCategory\Handlers\DeleteEcoCategoryHandler;
use Modules\Ecommerce\EcoCategory\Handlers\UpdateEcoCategoryHandler;
use Modules\Ecommerce\EcoCategory\Presenters\EcoCategoryPresenter;
use Modules\Ecommerce\EcoCategory\Requests\CreateEcoCategoryRequest;
use Modules\Ecommerce\EcoCategory\Requests\DeleteEcoCategoryRequest;
use Modules\Ecommerce\EcoCategory\Requests\GetEcoCategoryListRequest;
use Modules\Ecommerce\EcoCategory\Requests\GetEcoCategoryRequest;
use Modules\Ecommerce\EcoCategory\Requests\UpdateEcoCategoryRequest;
use Modules\Ecommerce\EcoCategory\Services\EcoCategoryCRUDService;
use Ramsey\Uuid\Uuid;

class EcoCategoryController extends Controller
{
    public function __construct(
        private EcoCategoryCRUDService $ecoCategoryService,
        private UpdateEcoCategoryHandler $updateEcoCategoryHandler,
        private DeleteEcoCategoryHandler $deleteEcoCategoryHandler,
    ) {
    }

    public function index(GetEcoCategoryListRequest $request): JsonResponse
    {
        $list = $this->ecoCategoryService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10),
            ['children', 'parent']
        );

        return Json::items(EcoCategoryPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetEcoCategoryRequest $request): JsonResponse
    {
        $item = $this->ecoCategoryService->get(Uuid::fromString($request->route('id')));

        $presenter = new EcoCategoryPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateEcoCategoryRequest $request): JsonResponse
    {
        $createdItem = $this->ecoCategoryService->create($request->createCreateEcoCategoryDTO());

        $presenter = new EcoCategoryPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateEcoCategoryRequest $request): JsonResponse
    {
        $command = $request->createUpdateEcoCategoryCommand();
        $this->updateEcoCategoryHandler->handle($command);

        $item = $this->ecoCategoryService->get($command->getId());

        $presenter = new EcoCategoryPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteEcoCategoryRequest $request): JsonResponse
    {
        $this->deleteEcoCategoryHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
