<?php

declare(strict_types=1);

namespace Modules\User\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\RoleAndPermission\Models\Permission;
use Modules\RoleAndPermission\Presenters\PermissionPresenter;
use Modules\RoleAndPermission\Presenters\RolePresenter;
use Modules\User\Handlers\AssignRoleForUserHandler;
use Modules\User\Handlers\DeleteUserHandler;
use Modules\User\Handlers\UpdateUserHandler;
use Modules\User\Presenters\UserPresenter;
use Modules\User\Requests\AssignRolesForUserRequest;
use Modules\User\Requests\CreateUserRequest;
use Modules\User\Requests\DeleteUserRequest;
use Modules\User\Requests\GetUserListRequest;
use Modules\User\Requests\GetUserRequest;
use Modules\User\Requests\UpdateUserRequest;
use Modules\User\Services\UserCRUDService;
use Ramsey\Uuid\Uuid;

class UserController extends Controller
{
    public function __construct(
        private UserCRUDService          $userService,
        private UpdateUserHandler        $updateUserHandler,
        private AssignRoleForUserHandler $assignRoleForUserHandler,
        private DeleteUserHandler        $deleteUserHandler,
    )
    {
    }

    public function index(GetUserListRequest $request): JsonResponse
    {
        $list = $this->userService->list(
            (int)$request->get('page', 1),
            (int)$request->get('per_page', 10)
        );

        return Json::buildItems(null, ['users' => UserPresenter::collection($list['data']), 'pagination' => $list['pagination']]);
    }

    public function show(GetUserRequest $request): JsonResponse
    {
        $item = $this->userService->get(Uuid::fromString($request->route('id')));

        $presenter = new UserPresenter($item);

        return Json::buildItems('user', $presenter->getData());
    }

    public function me()
    {
        $user = auth()->user();
        $userPresenter = new UserPresenter($user);
        return Json::buildItems('user', $userPresenter->getData());
    }

    public function store(CreateUserRequest $request): JsonResponse
    {
        $createdItem = $this->userService->create($request->createCreateUserDTO());

        $presenter = new UserPresenter($createdItem);

        return Json::buildItems('user', $presenter->getData());
    }

    public function update(UpdateUserRequest $request): JsonResponse
    {
        $command = $request->createUpdateUserCommand();
        $this->updateUserHandler->handle($command);

        $item = $this->userService->get($command->getId());

        $presenter = new UserPresenter($item);

        return Json::buildItems('user', $presenter->getData());
    }


    public function assignRolesForUser(AssignRolesForUserRequest $request): JsonResponse
    {
        $command = $request->createAssignRoleForUserCommand();
        $this->assignRoleForUserHandler->handle($command);
        return Json::buildItems('roles', "roles added successfully");
    }

    public function getMyPermissions()
    {
        $permissionPresenter = PermissionPresenter::collection(auth()->user()->permissions);
        return Json::buildItems("permissions", $permissionPresenter);
    }

    public function getMyRoles()
    {
        $permissionPresenter = RolePresenter::collection(auth()->user()->roles);
        return Json::buildItems("permissions", $permissionPresenter);
    }

    public function getPermissions()
    {

    }


    public function delete(DeleteUserRequest $request): JsonResponse
    {
        $this->deleteUserHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
