<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Modules\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\SubscriptionSystem\Modules\Handlers\DeleteModulesHandler;
use Modules\SubscriptionSystem\Modules\Handlers\UpdateModulesHandler;
use Modules\SubscriptionSystem\Modules\Presenters\ModulesPresenter;
use Modules\SubscriptionSystem\Modules\Requests\CreateModulesRequest;
use Modules\SubscriptionSystem\Modules\Requests\DeleteModulesRequest;
use Modules\SubscriptionSystem\Modules\Requests\GetModulesListRequest;
use Modules\SubscriptionSystem\Modules\Requests\GetModulesRequest;
use Modules\SubscriptionSystem\Modules\Requests\UpdateModulesRequest;
use Modules\SubscriptionSystem\Modules\Services\ModulesCRUDService;
use Ramsey\Uuid\Uuid;

class ModulesController extends Controller
{
    public function __construct(
        private ModulesCRUDService $modulesService,
        private UpdateModulesHandler $updateModulesHandler,
        private DeleteModulesHandler $deleteModulesHandler,
    ) {
    }

    public function index(GetModulesListRequest $request): JsonResponse
    {
        $list = $this->modulesService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(ModulesPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetModulesRequest $request): JsonResponse
    {
        $item = $this->modulesService->get(Uuid::fromString($request->route('id')));

        $presenter = new ModulesPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateModulesRequest $request): JsonResponse
    {
        $createdItem = $this->modulesService->create($request->createCreateModulesDTO());

        $presenter = new ModulesPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateModulesRequest $request): JsonResponse
    {
        $command = $request->createUpdateModulesCommand();
        $this->updateModulesHandler->handle($command);

        $item = $this->modulesService->get($command->getId());

        $presenter = new ModulesPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteModulesRequest $request): JsonResponse
    {
        $this->deleteModulesHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
