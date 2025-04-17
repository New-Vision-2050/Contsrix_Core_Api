<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserStatus\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\CompanyUser\Repositories\CompanyUserRepository;
use Modules\User\Repositories\UserRepository;
use Modules\UserInfo\UserStatus\Handlers\UpdateUserStatusHandler;
use Modules\UserInfo\UserStatus\Presenters\UserStatusPresenter;
use Modules\UserInfo\UserStatus\Requests\GetUserStatusRequest;
use Modules\UserInfo\UserStatus\Requests\UpdateUserStatusRequest;
use Modules\UserInfo\UserStatus\Services\UserStatusCRUDService;
use Ramsey\Uuid\Uuid;

class UserStatusController extends Controller
{
    public function __construct(
        private UserStatusCRUDService $userStatusService,
        private UpdateUserStatusHandler $updateUserStatusHandler,
        private UserRepository $userRepository,
        private CompanyUserRepository $companyUserRepository,
    ) {
    }

    public function index(GetUserStatusRequest $request): JsonResponse
    {


        $userId = Uuid::fromString($request->route('id'));

        $user = $this->userRepository->getUser($userId);

        $item = $this->userStatusService->get(
            Uuid::fromString($user->global_company_user_id),
        );


        $presenter = new UserStatusPresenter($item);

        return Json::item($presenter->getData());
    }

    public function updateStatus(UpdateUserStatusRequest $request)
    {
        $user = $this->userRepository->getUser(Uuid::fromString($request->route('id')));
        $companyUser = $this->companyUserRepository->getCompanyUserGlobalId(Uuid::fromString($user->global_company_user_id));

        $command = $request->createUpdateUserStatusCommand();
        $command->companyUserId = Uuid::fromString($companyUser->id);

        $this->updateUserStatusHandler->handle($command);

        $item = $this->userStatusService->get(Uuid::fromString($companyUser->global_id));

        $presenter = new UserStatusPresenter($item);

        return Json::item($presenter->getData());
    }

}
