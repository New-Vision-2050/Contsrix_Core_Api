<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Modules\RoleAndPermission\Handlers\AssignPermissionsToRoleHandler;
use Modules\RoleAndPermission\Handlers\DeleteRoleHandler;
use Modules\RoleAndPermission\Handlers\UpdateRoleHandler;
use Modules\RoleAndPermission\Presenters\PermissionPresenter;
use Modules\RoleAndPermission\Presenters\RolePresenter;
use Modules\RoleAndPermission\Presenters\RoleWidgetsPresenter;
use Modules\RoleAndPermission\Presenters\RoleWithPermissionPresenter;
use Modules\RoleAndPermission\Requests\AssignPermissionToRoleRequest;
use Modules\RoleAndPermission\Requests\CreateRoleRequest;
use Modules\RoleAndPermission\Requests\DeleteRoleRequest;
use Modules\RoleAndPermission\Requests\GetPermissionRequest;
use Modules\RoleAndPermission\Requests\GetRoleListRequest;
use Modules\RoleAndPermission\Requests\GetRoleRequest;
use Modules\RoleAndPermission\Requests\SetStatusRoleRequest;
use Modules\RoleAndPermission\Requests\UpdateRoleRequest;
use Modules\RoleAndPermission\Services\RoleCRUDService;
use Modules\RoleAndPermission\Services\RoleService;
use Ramsey\Uuid\Uuid;

class RoleController extends Controller
{
    public function __construct(
        private readonly RoleCRUDService $roleCRUDService,
        private readonly RoleService $roleService,
        private readonly UpdateRoleHandler $updateRoleHandler,
        private readonly AssignPermissionsToRoleHandler $assignPermissionsToRoleHandler,
        private readonly DeleteRoleHandler $deleteRoleHandler,
    ) {
    }

    public function index(GetRoleListRequest $request): JsonResponse
    {
        $list = $this->roleCRUDService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items( RolePresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetRoleRequest $request): JsonResponse
    {
        $item = $this->roleCRUDService->get(Uuid::fromString($request->route('id')));

        $presenter = new RoleWithPermissionPresenter($item);

        return Json::item($presenter->getData());
    }



    public function store(CreateRoleRequest $request): JsonResponse
    {
        $createdItem = $this->roleCRUDService->create($request->createCreateRoleDTO(),$request->createCreatePermissionForRoleDTO());

        $presenter = new RolePresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateRoleRequest $request): JsonResponse
    {
        // Get the role being updated to check if it's super-admin
        $roleId = Uuid::fromString($request->route('id'));
        $existingRole = $this->roleCRUDService->get($roleId);

        // Prevent updating the super-admin role
        if ($existingRole->name === 'super-admin') {
            return Json::error('The super-admin role cannot be updated', httpStatus: 400);
        }

        $command = $request->createUpdateRoleCommand();
        $this->updateRoleHandler->handle($command);

        $item = $this->roleCRUDService->get($command->getId());

        $presenter = new RolePresenter($item);

        return Json::item($presenter->getData());
    }

    public function assignPermissionToRole(AssignPermissionToRoleRequest $request): JsonResponse
    {
        $command = $request->createAssignPermissionToRoleCommand();
        $this->assignPermissionsToRoleHandler->handle($command);

        return Json::item("msg", "permissions added successfully");
    }

    public function getPermissions(GetPermissionRequest $request)
    {
        $role = $this->roleCRUDService->get(Uuid::fromString($request->route('id')));
        $permissionRepresenter = PermissionPresenter::collection($role->permissions);
        return Json::item("permissions", $permissionRepresenter);
    }



    public function delete(DeleteRoleRequest $request): JsonResponse
    {
        $this->deleteRoleHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    /**
     * Set the status of a role (activate or deactivate).
     *
     * @param SetStatusRoleRequest $request
     * @return JsonResponse
     */
    public function setStatus(SetStatusRoleRequest $request): JsonResponse
    {
        try {
            $role = $this->roleCRUDService->setStatus(
                Uuid::fromString($request->getRoleId()),
                $request->getStatus()
            );

            $message = $request->getStatus() ? 'Role activated successfully.' : 'Role deactivated successfully.';

            $presenter = new RolePresenter($role);

            return Json::item($presenter->getData(), message: $message);
        } catch (ValidationException $e) {
            return Json::error($e->errors()['status'][0] ?? 'Cannot update role status', 422);
        }
    }

    public function getRoleWidgetsData(): JsonResponse
    {
        $widgetsData = $this->roleService->getRoleWidgetsData();

        return Json::item(
            (new RoleWidgetsPresenter($widgetsData))->getData()
        );
    }
}
