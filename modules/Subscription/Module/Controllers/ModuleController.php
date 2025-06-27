<?php

declare(strict_types=1);

namespace Modules\Subscription\Module\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Subscription\Module\Handlers\DeleteModuleHandler;
use Modules\Subscription\Module\Handlers\UpdateModuleHandler;
use Modules\Subscription\Module\Presenters\ModulePresenter;
use Modules\Subscription\Module\Requests\CreateModuleRequest;
use Modules\Subscription\Module\Requests\DeleteModuleRequest;
use Modules\Subscription\Module\Requests\GetModuleListRequest;
use Modules\Subscription\Module\Requests\GetModuleRequest;
use Modules\Subscription\Module\Requests\UpdateModuleRequest;
use Modules\Subscription\Module\Services\ModuleCRUDService;
use Ramsey\Uuid\Uuid;

class ModuleController extends Controller
{
    public function __construct(
        private ModuleCRUDService $moduleService,
        private UpdateModuleHandler $updateModuleHandler,
        private DeleteModuleHandler $deleteModuleHandler,
    ) {
    }

    public function index(GetModuleListRequest $request): JsonResponse
    {
        $list = $this->moduleService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(ModulePresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetModuleRequest $request): JsonResponse
    {
        $item = $this->moduleService->get(Uuid::fromString($request->route('id')));

        $presenter = new ModulePresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateModuleRequest $request): JsonResponse
    {
        $createdItem = $this->moduleService->create($request->createCreateModuleDTO());

        $presenter = new ModulePresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateModuleRequest $request): JsonResponse
    {
        $command = $request->createUpdateModuleCommand();
        $this->updateModuleHandler->handle($command);

        $item = $this->moduleService->get($command->getId());

        $presenter = new ModulePresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteModuleRequest $request): JsonResponse
    {
        $this->deleteModuleHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
