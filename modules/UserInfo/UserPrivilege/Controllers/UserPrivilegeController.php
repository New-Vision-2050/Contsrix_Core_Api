<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserPrivilege\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\User\Repositories\UserRepository;
use Modules\UserInfo\UserPrivilege\Handlers\DeleteUserPrivilegeHandler;
use Modules\UserInfo\UserPrivilege\Handlers\UpdateUserPrivilegeHandler;
use Modules\UserInfo\UserPrivilege\Presenters\UserPrivilegePresenter;
use Modules\UserInfo\UserPrivilege\Requests\CreateUserPrivilegeRequest;
use Modules\UserInfo\UserPrivilege\Requests\DeleteUserPrivilegeRequest;
use Modules\UserInfo\UserPrivilege\Requests\GetUserPrivilegeListRequest;
use Modules\UserInfo\UserPrivilege\Requests\GetUserPrivilegeRequest;
use Modules\UserInfo\UserPrivilege\Requests\UpdateUserPrivilegeRequest;
use Modules\UserInfo\UserPrivilege\Services\UserPrivilegeCRUDService;
use Ramsey\Uuid\Uuid;

class UserPrivilegeController extends Controller
{
    public function __construct(
        private UserPrivilegeCRUDService $userPrivilegeService,
        private UpdateUserPrivilegeHandler $updateUserPrivilegeHandler,
        private DeleteUserPrivilegeHandler $deleteUserPrivilegeHandler,
        private UserRepository $userRepository
    ) {
    }

    public function index(GetUserPrivilegeListRequest $request): JsonResponse
    {
        $userId = Uuid::fromString($request->route('id'));
        $user = $this->userRepository->getUser($userId);

        $list = $this->userPrivilegeService->list(
            Uuid::fromString($user->company_id),
            Uuid::fromString($user->global_company_user_id),
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(UserPrivilegePresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetUserPrivilegeRequest $request): JsonResponse
    {
        $item = $this->userPrivilegeService->get(Uuid::fromString($request->route('id')));

        $presenter = new UserPrivilegePresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateUserPrivilegeRequest $request): JsonResponse
    {
        $createCreateUserPrivilegeDTO = $request->createCreateUserPrivilegeDTO();
        $userId = Uuid::fromString($request->input('user_id'));

        $user = $this->userRepository->getUser($userId);
        $createCreateUserPrivilegeDTO->company_id = $user->company_id;
        $createCreateUserPrivilegeDTO->global_id = $user->global_company_user_id;

        $createdItem = $this->userPrivilegeService->create($createCreateUserPrivilegeDTO);

        $presenter = new UserPrivilegePresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateUserPrivilegeRequest $request): JsonResponse
    {
        $command = $request->createUpdateUserPrivilegeCommand();
        $this->updateUserPrivilegeHandler->handle($command);

        $item = $this->userPrivilegeService->get($command->getId());

        $presenter = new UserPrivilegePresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteUserPrivilegeRequest $request): JsonResponse
    {
        $this->deleteUserPrivilegeHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
