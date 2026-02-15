<?php

declare(strict_types=1);

namespace Modules\User\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Company\CompanyCore\Notifications\SendDomainForUser;
use Modules\Company\CompanyCore\Notifications\SendDomainForUserEmailAndSMS;
use Modules\Company\ManagementHierarchy\Presenters\ManagementHierarchyPresenter;
use Modules\Company\ManagementHierarchy\Presenters\ManagementHierarchySimpleDataPresenter;
use Modules\CompanyUser\Enum\CompanyUserRole;
use Modules\CompanyUser\Requests\Broker\GetBrokerRequest;
use Modules\User\Presenters\UserBranchesPresenter;
use Modules\User\Presenters\UserRolesPresenter;
use Modules\User\Presenters\UserWithPermissionPresenter;
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
use Modules\User\Requests\ChangeUserRoleStatusRequest;
use Modules\User\Requests\CreateUserRequest;
use Modules\User\Requests\DeleteUserRequest;
use Modules\User\Requests\GetAdminUsersRequest;
use Modules\User\Requests\GetUserAuditListRequest;
use Modules\User\Requests\GetUserByGlobalIdRequest;
use Modules\User\Requests\GetUserListRequest;
use Modules\User\Requests\GetUserRequest;
use Modules\User\Requests\GetUserRolesAndPermissionRequest;
use Modules\User\Requests\UpdateUserLoginWayRequest;
use Modules\User\Requests\UpdateUserRequest;
use Modules\User\Services\UserAuditService;
use Modules\User\Services\UserCRUDService;
use Modules\User\Services\UserRoleAndPermissionService;
use Modules\CompanyUser\Repositories\CompanyUserRepository;
use Modules\CompanyUser\Requests\SendEmailToUserRequest;
use Modules\CompanyUser\Services\CompanyUserCRUDService;
use Modules\NotificationSettings\Services\FirebaseNotificationService;
use Ramsey\Uuid\Uuid;
use Illuminate\Http\Request;
use Modules\User\Requests\GetInfoAlertRequest;
use Modules\User\Presenters\InfoAlertPresenter;

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
        private CompanyUserRepository        $companyUserRepository,
        private CompanyUserCRUDService       $companyUserCRUDService,
    )
    {
    }

    public function index(GetUserListRequest $request): JsonResponse
    {
        $list = $this->userService->list(
            (int)$request->get('page', 1),
            (int)$request->get('per_page', 10000)
        );

        return Json::items(UserPresenter::collection($list['data']),paginationSettings: $list['pagination']);
    }
    public function getByRole(GetUserListRequest $request): JsonResponse
    {
        $role = CompanyUserRole::EMPLOYEE->value ;
        if($request->has("role"))
        {
            $role = $request->role;
        }
        $list = $this->userService->listByRole(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10),
            $role
        );



        return Json::items(UserRolesPresenter::collection($list['data'],$role),paginationSettings: $list['pagination']);
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
        $userPresenter = new UserWithPermissionPresenter($user);
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

    public function getUserByGlobalId(GetUserByGlobalIdRequest $userByEmailRequest)
    {

        $branchesWithRole =  $this->userService->getUserByGlobalIdWithBranches($userByEmailRequest->global_id,$userByEmailRequest->role);
        return Json::item( ManagementHierarchySimpleDataPresenter::collection($branchesWithRole?->managementHierarchy?$branchesWithRole?->managementHierarchy:[]));
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
        $userIds = $request->input('ids');
        $format = strtolower($request->input('format', 'xlsx'));

        if (!in_array($format, ['xlsx', 'csv'])) {
            return Json::error('Invalid format. Supported formats are: xlsx, csv', 400);
        }

        $export = $this->userService->export($userIds, $format);
        $filename = 'users_export_' . now()->format('Y-m-d_H-i-s');

        return Excel::download($export, $filename . '.' . $format);
    }

    /**
     * Change the status of a user role in company_user_company table
     *
     * @param ChangeUserRoleStatusRequest $request
     * @return JsonResponse
     */
    public function changeUserRoleStatus(ChangeUserRoleStatusRequest $request): JsonResponse
    {
        try {
            $updatedRole = $this->companyUserRepository->updateUserRoleStatus(
                $request->getUserId(),
                $request->getRoleId(),
                $request->getStatus()
            );

            return Json::success(__("validation.updated_successfully"), [
                'user_role' => [
                    'id' => $updatedRole->id,
                    'user_id' => $request->getUserId(),
                    'role_id' => $request->getRoleId(),
                    'status' => $updatedRole->status,
                    'updated_at' => $updatedRole->updated_at
                ]
            ]);
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    public function sendEmail(SendEmailToUserRequest $request)
    {
        $userId = $request->getUserId();
        $companyId = tenant('id');

        // Get the company user
        $user = $this->userService->get($userId);

        $companyUser = $this->companyUserRepository->getCompanyUserGlobalId(UUid::fromString($user->global_company_user_id));

        $data = [
            "name" => $user->name,
            "company_name" => $user->company?->name,
            "domain_name" => "https://".$user->company?->domains()->first()?->domain,
            "serial_no" => $user->company?->serial_no
        ];
        $user->notify(new SendDomainForUserEmailAndSMS($data,[$request->get("type","mail")]));


        // Send email using the service method
//        $this->companyUserCRUDService->sendEmailAssignToCompanyToUser($companyUser, $companyId);

        return Json::item([
            'message' => 'Email sent successfully',
            'user_id' => $userId,
            'company_id' => $companyId,
            "company_user"=>$companyUser
        ]);
    }

    /**
     * Test notification to all users with FCM tokens
     */
    public function testNotification(): JsonResponse
    {
        $users = \Modules\User\Models\User::whereNotNull('fcm_token')->get();
        $sentCount = 0;

        foreach($users as $user){
            $title = 'Test Notification';
            $body = 'This is a test notification from the system';

            if (FirebaseNotificationService::send($user->fcm_token, $title, $body)) {
                $sentCount++;
            }
        }

        return Json::done("Test notification sent to {$sentCount} users");
    }

    /**
     * Test silent notification to all users with FCM tokens
     */
    public function testSilentNotification(): JsonResponse
    {
        $users = \Modules\User\Models\User::whereNotNull('fcm_token')->get();
        $sentCount = 0;

        foreach($users as $user){
            $data = [
                'type' => 'silent_update',
                'action' => 'refresh_data',
                'timestamp' => now()->timestamp,
                'user_id' => $user->id
            ];

            if (FirebaseNotificationService::sendSilent($user->fcm_token, $data)) {
                $sentCount++;
            }
        }

        return Json::done("Silent notification sent to {$sentCount} users");
    }

    /**
     * Update user's FCM token
     */
    public function updateFcmToken(Request $request): JsonResponse
    {
        $request->validate([
            'fcm_token' => 'required|string'
        ]);

        $user = auth()->user();
        $user->fcm_token = $request->fcm_token;
        $user->save();

        return Json::done('FCM token updated successfully');
    }


    public function getInfoAlert(GetInfoAlertRequest $request): JsonResponse
    {
        $dto = $request->toDTO();
        $alerts = $this->userService->getInfoAlerts($dto->userId, $dto->type, $dto->branchId);

        return Json::items(InfoAlertPresenter::collection($alerts));
    }



}
