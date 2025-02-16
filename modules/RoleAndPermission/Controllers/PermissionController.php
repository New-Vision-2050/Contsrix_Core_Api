<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Controllers;

use BasePackage\Shared\Facade\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\RoleAndPermission\Handlers\DeletePermissionHandler;
use Modules\RoleAndPermission\Handlers\UpdatePermissionHandler;
use Modules\RoleAndPermission\Presenters\PermissionPresenter;
use Modules\RoleAndPermission\Presenters\RoleAndPermissionPresenter;
use Modules\RoleAndPermission\Requests\CreatePermissionRequest;
use Modules\RoleAndPermission\Requests\DeletePermissionRequest;
use Modules\RoleAndPermission\Requests\GetPermissionListRequest;
use Modules\RoleAndPermission\Requests\GetPermissionRequest;
use Modules\RoleAndPermission\Requests\UpdatePermissionRequest;
use Modules\RoleAndPermission\Services\PermissionCRUDService;
use Ramsey\Uuid\Uuid;

class PermissionController extends Controller
{
    public function __construct(
        private PermissionCRUDService $permissionService,
        private UpdatePermissionHandler $updatePermissionHandler,
        private DeletePermissionHandler $deletePermissionHandler


    ) {
    }

    public function index(GetPermissionListRequest $request): JsonResponse
    {
        $list = $this->permissionService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::buildItems(null,['permissions' => PermissionPresenter::collection($list['data']),'pagination' => $list['pagination']]);
    }

    public function show(GetPermissionRequest $request): JsonResponse
    {
        $item = $this->permissionService->get(Uuid::fromString($request->route('id')));

        $presenter = new PermissionPresenter($item);

        return Json::buildItems('permissions', $presenter->getData());
    }

    public function store(CreatePermissionRequest $request): JsonResponse
    {
        $createdItem = $this->permissionService->create($request->createCreatePermissionDTO());

        $presenter = new PermissionPresenter($createdItem);

        return Json::buildItems('permissions', $presenter->getData());
    }

    public function update(UpdatePermissionRequest $request): JsonResponse
    {
        $command = $request->createUpdatePermissionCommand();
        $this->updatePermissionHandler->handle($command);

        $item = $this->permissionService->get($command->getId());

        $presenter = new permissionPresenter($item);

        return Json::buildItems('permissions', $presenter->getData());
    }

    public function delete(DeletePermissionRequest $request): JsonResponse
    {
        $this->deletePermissionHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }


}
