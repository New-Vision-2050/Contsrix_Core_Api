<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Controllers;

use BasePackage\Shared\Facade\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\RoleAndPermission\Handlers\DeleteRoleAndPermissionHandler;
use Modules\RoleAndPermission\Handlers\UpdateRoleAndPermissionHandler;
use Modules\RoleAndPermission\Presenters\RoleAndPermissionPresenter;
use Modules\RoleAndPermission\Requests\CreateRoleAndPermissionRequest;
use Modules\RoleAndPermission\Requests\DeleteRoleAndPermissionRequest;
use Modules\RoleAndPermission\Requests\GetRoleAndPermissionListRequest;
use Modules\RoleAndPermission\Requests\GetRoleAndPermissionRequest;
use Modules\RoleAndPermission\Requests\UpdateRoleAndPermissionRequest;
use Modules\RoleAndPermission\Services\RoleAndPermissionCRUDService;
use Ramsey\Uuid\Uuid;

class PermissionController extends Controller
{
    public function __construct(
        private RoleAndPermissionCRUDService $roleAndPermissionService,
        private UpdateRoleAndPermissionHandler $updateRoleAndPermissionHandler,
        private DeleteRoleAndPermissionHandler $deleteRoleAndPermissionHandler,
    ) {
    }

    public function index(GetRoleAndPermissionListRequest $request): JsonResponse
    {
        $list = $this->roleAndPermissionService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::buildItems(null,['role_and_permissions' => RoleAndPermissionPresenter::collection($list['data']),'pagination' => $list['pagination']]);
    }

    public function show(GetRoleAndPermissionRequest $request): JsonResponse
    {
        $item = $this->roleAndPermissionService->get(Uuid::fromString($request->route('id')));

        $presenter = new RoleAndPermissionPresenter($item);

        return Json::buildItems('role_and_permission', $presenter->getData());
    }

    public function store(CreateRoleAndPermissionRequest $request): JsonResponse
    {
        $createdItem = $this->roleAndPermissionService->create($request->createCreateRoleAndPermissionDTO());

        $presenter = new RoleAndPermissionPresenter($createdItem);

        return Json::buildItems('role_and_permission', $presenter->getData());
    }

    public function update(UpdateRoleAndPermissionRequest $request): JsonResponse
    {
        $command = $request->createUpdateRoleAndPermissionCommand();
        $this->updateRoleAndPermissionHandler->handle($command);

        $item = $this->roleAndPermissionService->get($command->getId());

        $presenter = new RoleAndPermissionPresenter($item);

        return Json::buildItems('role_and_permission', $presenter->getData());
    }

    public function delete(DeleteRoleAndPermissionRequest $request): JsonResponse
    {
        $this->deleteRoleAndPermissionHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
