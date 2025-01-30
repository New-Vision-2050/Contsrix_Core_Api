<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Modules\RoleAndPermission\Handlers\AssignPermissionsToRoleHandler;
use Modules\RoleAndPermission\Handlers\DeleteRoleHandler;
use Modules\RoleAndPermission\Handlers\UpdateRoleHandler;
use Modules\RoleAndPermission\Presenters\PermissionPresenter;
use Modules\RoleAndPermission\Presenters\RoleAndPermissionPresenter;
use Modules\RoleAndPermission\Presenters\RolePresenter;
use Modules\RoleAndPermission\Requests\AssignPermissionToRoleRequest;
use Modules\RoleAndPermission\Requests\CreateRoleRequest;
use Modules\RoleAndPermission\Requests\DeleteRoleRequest;
use Modules\RoleAndPermission\Requests\GetPermissionRequest;
use Modules\RoleAndPermission\Requests\GetRoleListRequest;
use Modules\RoleAndPermission\Requests\GetRoleRequest;
use Modules\RoleAndPermission\Requests\UpdateRoleRequest;
use Modules\RoleAndPermission\Services\RoleCRUDService;
use Ramsey\Uuid\Uuid;

class RoleController extends Controller
{
    public function __construct(
        private RoleCRUDService $roleService,
        private UpdateRoleHandler $updateRoleHandler,
        private AssignPermissionsToRoleHandler $assignPermissionsToRoleHandler,
        private DeleteRoleHandler $deleteRoleHandler,
    ) {
    }

    public function index(GetRoleListRequest $request): JsonResponse
    {
        $list = $this->roleService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::buildItems(null,['role' => RolePresenter::collection($list['data']),'pagination' => $list['pagination']]);
    }

    public function show(GetRoleRequest $request): JsonResponse
    {
        $item = $this->roleService->get(Uuid::fromString($request->route('id')));

        $presenter = new RolePresenter($item);

        return Json::buildItems('role', $presenter->getData());
    }

    public function store(CreateRoleRequest $request): JsonResponse
    {
        $createdItem = $this->roleService->create($request->createCreateRoleDTO());

        $presenter = new RolePresenter($createdItem);

        return Json::buildItems('role', $presenter->getData());
    }

    public function update(UpdateRoleRequest $request): JsonResponse
    {
        $command = $request->createUpdateRoleCommand();
        $this->updateRoleHandler->handle($command);

        $item = $this->roleService->get($command->getId());

        $presenter = new RolePresenter($item);

        return Json::buildItems('role', $presenter->getData());
    }

    public function assignPermissionToRole(AssignPermissionToRoleRequest $request): JsonResponse
    {
        $command = $request->createAssignPermissionToRoleCommand();
        $this->assignPermissionsToRoleHandler->handle($command);

        return Json::buildItems("msg","permissions added successfully ");
    }

    public function getPermissions(GetPermissionRequest $request)
    {
        $role = $this->roleService->get(Uuid::fromString($request->route('id')));
        $permissionRepresenter = PermissionPresenter::collection($role->permissions);
        return Json::buildItems("permissions", $permissionRepresenter);
    }



    public function delete(DeleteRoleRequest $request): JsonResponse
    {
        $this->deleteRoleHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
