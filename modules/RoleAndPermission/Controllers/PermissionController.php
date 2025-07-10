<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\RoleAndPermission\Handlers\DeletePermissionHandler;
use Modules\RoleAndPermission\Handlers\UpdatePermissionHandler;
use Modules\RoleAndPermission\Presenters\PermissionLookupPresenter;
use Modules\RoleAndPermission\Presenters\PermissionPresenter;
use Modules\RoleAndPermission\Requests\CreatePermissionRequest;
use Modules\RoleAndPermission\Requests\DeletePermissionRequest;
use Modules\RoleAndPermission\Requests\GetPermissionListRequest;
use Modules\RoleAndPermission\Requests\GetPermissionRequest;
use Modules\RoleAndPermission\Requests\SetStatusPermissionRequest;
use Modules\RoleAndPermission\Requests\UpdatePermissionRequest;
use Modules\RoleAndPermission\Services\PermissionCRUDService;
use Modules\RoleAndPermission\Services\PermissionLookupService;
use Ramsey\Uuid\Uuid;

class PermissionController extends Controller
{
    public function __construct(
        private PermissionCRUDService   $permissionService,
        private UpdatePermissionHandler $updatePermissionHandler,
        private DeletePermissionHandler $deletePermissionHandler,
        private PermissionLookupService $permissionLookupService,
        private PermissionLookupPresenter $permissionLookupPresenter
    )
    {
    }

    public function index(GetPermissionListRequest $request): JsonResponse
    {
        $list = $this->permissionService->list(
            (int)$request->get('page', 1),
            (int)$request->get('per_page', 10)
        );

        return Json::items( PermissionPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function permissionAsLookup(GetPermissionListRequest $request): JsonResponse
    {
        $permissions = $this->permissionLookupService->getPermissionsForCompany();
        $presented = $this->permissionLookupPresenter->present($permissions);
        return Json::item($presented);
    }

    public function show(GetPermissionRequest $request): JsonResponse
    {
        $item = $this->permissionService->get(Uuid::fromString($request->route('id')));

        $presenter = new PermissionPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreatePermissionRequest $request): JsonResponse
    {
        $createdItem = $this->permissionService->create($request->createCreatePermissionDTO());

        $presenter = new PermissionPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdatePermissionRequest $request): JsonResponse
    {
        $command = $request->createUpdatePermissionCommand();
        $this->updatePermissionHandler->handle($command);

        $item = $this->permissionService->get($command->getId());

        $presenter = new PermissionPresenter($item);

        return Json::item($presenter->getData());
    }

    public function delete(DeletePermissionRequest $request): JsonResponse
    {
        $this->deletePermissionHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    /**
     * Set the status of a permission (activate or deactivate).
     *
     * @param SetStatusPermissionRequest $request
     * @return JsonResponse
     */
    public function setStatus(SetStatusPermissionRequest $request): JsonResponse
    {
        $permission = $this->permissionService->setStatus(
            $request->getPermissionId(),
            $request->getStatus()
        );

        $message = $request->getStatus() ? 'Permission activated successfully.' : 'Permission deactivated successfully.';

        $presenter = new PermissionPresenter($permission);

        return Json::item($presenter->getData(), message: $message);
    }
}
