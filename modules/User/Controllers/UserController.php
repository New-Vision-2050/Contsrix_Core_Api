<?php

declare(strict_types=1);

namespace Modules\User\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;
use Modules\User\Requests\ExportUsersRequest;
use Modules\Company\CompanyCore\Presenters\CompanyPresenter;
use Modules\RoleAndPermission\Presenters\PermissionPresenter;
use Modules\RoleAndPermission\Presenters\RolePresenter;
use Modules\User\Handlers\AssignRoleForUserHandler;
use Modules\User\Handlers\DeleteUserHandler;
use Modules\User\Handlers\UpdateUserHandler;
use Modules\User\Handlers\UpdateUserLoginWayHandler;
use Modules\User\Presenters\UserPresenter;
use Modules\User\Presenters\UserWithLoginWayPresenter;
use Modules\User\Requests\AssignRolesForUserRequest;
use Modules\User\Requests\CreateUserRequest;
use Modules\User\Requests\DeleteUserRequest;
use Modules\User\Requests\GetAdminUsersRequest;
use Modules\User\Requests\GetUserAuditListRequest;
use Modules\User\Requests\GetUserListRequest;
use Modules\User\Requests\GetUserRequest;
use Modules\User\Requests\GetUserRolesAndPermissionRequest;
use Modules\User\Requests\UpdateUserLoginWayRequest;
use Modules\User\Requests\UpdateUserRequest;
use Modules\User\Services\UserAuditService;
use Modules\User\Services\UserCRUDService;
use Modules\User\Services\UserRoleAndPermissionService;
use Ramsey\Uuid\Uuid;

class UserController extends Controller
{
    public function __construct(
        private UserCRUDService              $userService,
        private UserAuditService             $userAuditService,
        private UserRoleAndPermissionService $userRoleAndPermissionService,
        private UpdateUserHandler            $updateUserHandler,
        private UpdateUserLoginWayHandler    $updateUserLoginWayHandler,
        private AssignRoleForUserHandler     $assignRoleForUserHandler,
        private DeleteUserHandler            $deleteUserHandler,
    )
    {
    }

    public function index(GetUserListRequest $request): JsonResponse
    {
        $list = $this->userService->list(
            (int)$request->get('page', 1),
            (int)$request->get('per_page', 10)
        );

        return Json::items(UserPresenter::collection($list['data']),paginationSettings: $list['pagination']);
    }

    public function show(GetUserRequest $request): JsonResponse
    {
        $item = $this->userService->get(Uuid::fromString($request->route('id')));

        $presenter = new UserPresenter($item);

        return Json::item($presenter->getData());
    }

    public function me()
    {
        $user = auth()->user();
        $userPresenter = new UserPresenter($user);
        return Json::item($userPresenter->getData());
    }

    public function store(CreateUserRequest $request): JsonResponse
    {
        $createdItem = $this->userService->create($request->createCreateUserDTO());

        $presenter = new UserPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateUserRequest $request): JsonResponse
    {
        $command = $request->createUpdateUserCommand();
        $this->updateUserHandler->handle($command);

        $item = $this->userService->get($command->getId());

        $presenter = new UserPresenter($item);

        return Json::item($presenter->getData());
    }

    public function updateLoginWay(UpdateUserLoginWayRequest $request): JsonResponse
    {

        $command = $request->createUpdateUserLoginWayCommand();
        $this->updateUserLoginWayHandler->handle($command);

        $user = $this->userService->get($command->getId());

        $presenter = new UserWithLoginWayPresenter($user);

        return Json::item($presenter->getData());

    }


    public function assignRolesForUser(AssignRolesForUserRequest $request): JsonResponse
    {
        $command = $request->createAssignRoleForUserCommand();
        $this->assignRoleForUserHandler->handle($command);
        return Json::success(__("validation.created_successfully"));
    }

    public function getMyPermissions()
    {
        $permissions = $this->userRoleAndPermissionService->getPermissions(auth()->user()->id);

        $permissionPresenter = PermissionPresenter::collection($permissions);

        return Json::item($permissionPresenter);
    }

    public function getMyRoles()
    {
        $roles = $this->userRoleAndPermissionService->getRoles(auth()->user()->id);
        $permissionPresenter = RolePresenter::collection($roles);
        return Json::item($permissionPresenter);
    }

    public function getPermissions(GetUserRolesAndPermissionRequest $request)
    {
        $permissions = $this->userRoleAndPermissionService->getPermissions(Uuid::fromString($request->route('id')));
        $permissionPresenter = PermissionPresenter::collection($permissions);
        return Json::item($permissionPresenter);
    }

    public function getRoles(GetUserRolesAndPermissionRequest $request)
    {
        $roles = $this->userRoleAndPermissionService->getRoles(Uuid::fromString($request->route('id')));
        $rolePresenter = RolePresenter::collection($roles);
        return Json::item($rolePresenter);
    }


    public function delete(DeleteUserRequest $request): JsonResponse
    {
        $this->deleteUserHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    public function getAudites(GetUserAuditListRequest $request)
    {
        $list = $this->userAuditService->listPaginated(
            Uuid::fromString($request->route('id')),
            (int)$request->get('page', 1),
            (int)$request->get('per_page', 10)
        );

        return Json::item(['audits' => $list["data"], 'pagination' => $list["pagination"]]);
    }

    public function getAvailableTenantsForAuthUser()
    {

        return Json::items(CompanyPresenter::collection($this->userService->getAvailableTenantForUser(auth()->user()->id)));
    }

    public function getAdminUsers(GetAdminUsersRequest $request): JsonResponse
    {
        $list = $this->userService->getAdminUsersFromCentralCompanies(
            (int)$request->get('page', 1),
            (int)$request->get('per_page', 10)
        );

        return Json::items(UserPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    /**
     * Export users data as Excel/CSV
     *
     * @param ExportUsersRequest $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export(ExportUsersRequest $request)
    {
        $userIds = $request->input('user_ids');
        $format = strtolower($request->input('format', 'xlsx'));
        
        if (!in_array($format, ['xlsx', 'csv'])) {
            return Json::error('Invalid format. Supported formats are: xlsx, csv', 400);
        }

        $export = $this->userService->export($userIds, $format);
        $filename = 'users_export_' . now()->format('Y-m-d_H-i-s');

        return Excel::download($export, $filename . '.' . $format);
    }
}
