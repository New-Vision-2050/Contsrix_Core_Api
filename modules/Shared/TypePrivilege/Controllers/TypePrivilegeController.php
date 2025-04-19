<?php

declare(strict_types=1);

namespace Modules\Shared\TypePrivilege\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Shared\TypePrivilege\Handlers\DeleteTypePrivilegeHandler;
use Modules\Shared\TypePrivilege\Handlers\UpdateTypePrivilegeHandler;
use Modules\Shared\TypePrivilege\Presenters\TypePrivilegePresenter;
use Modules\Shared\TypePrivilege\Requests\CreateTypePrivilegeRequest;
use Modules\Shared\TypePrivilege\Requests\DeleteTypePrivilegeRequest;
use Modules\Shared\TypePrivilege\Requests\GetTypePrivilegeListRequest;
use Modules\Shared\TypePrivilege\Requests\GetTypePrivilegeRequest;
use Modules\Shared\TypePrivilege\Requests\UpdateTypePrivilegeRequest;
use Modules\Shared\TypePrivilege\Services\TypePrivilegeCRUDService;
use Ramsey\Uuid\Uuid;

class TypePrivilegeController extends Controller
{
    public function __construct(
        private TypePrivilegeCRUDService $typePrivilegeService,
        private UpdateTypePrivilegeHandler $updateTypePrivilegeHandler,
        private DeleteTypePrivilegeHandler $deleteTypePrivilegeHandler,
    ) {
    }

    public function index(GetTypePrivilegeListRequest $request): JsonResponse
    {
        $list = $this->typePrivilegeService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(TypePrivilegePresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetTypePrivilegeRequest $request): JsonResponse
    {
        $item = $this->typePrivilegeService->get(Uuid::fromString($request->route('id')));

        $presenter = new TypePrivilegePresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateTypePrivilegeRequest $request): JsonResponse
    {
        $createdItem = $this->typePrivilegeService->create($request->createCreateTypePrivilegeDTO());

        $presenter = new TypePrivilegePresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateTypePrivilegeRequest $request): JsonResponse
    {
        $command = $request->createUpdateTypePrivilegeCommand();
        $this->updateTypePrivilegeHandler->handle($command);

        $item = $this->typePrivilegeService->get($command->getId());

        $presenter = new TypePrivilegePresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteTypePrivilegeRequest $request): JsonResponse
    {
        $this->deleteTypePrivilegeHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
