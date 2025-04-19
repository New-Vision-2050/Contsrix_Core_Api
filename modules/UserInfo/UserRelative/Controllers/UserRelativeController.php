<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserRelative\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\User\Repositories\UserRepository;
use Modules\UserInfo\UserRelative\Handlers\DeleteUserRelativeHandler;
use Modules\UserInfo\UserRelative\Handlers\UpdateUserRelativeHandler;
use Modules\UserInfo\UserRelative\Presenters\UserRelativePresenter;
use Modules\UserInfo\UserRelative\Requests\CreateUserRelativeRequest;
use Modules\UserInfo\UserRelative\Requests\DeleteUserRelativeRequest;
use Modules\UserInfo\UserRelative\Requests\GetUserRelativeListRequest;
use Modules\UserInfo\UserRelative\Requests\GetUserRelativeRequest;
use Modules\UserInfo\UserRelative\Requests\UpdateUserRelativeRequest;
use Modules\UserInfo\UserRelative\Services\UserRelativeCRUDService;
use Ramsey\Uuid\Uuid;

class UserRelativeController extends Controller
{
    public function __construct(
        private UserRelativeCRUDService $userRelativeService,
        private UpdateUserRelativeHandler $updateUserRelativeHandler,
        private DeleteUserRelativeHandler $deleteUserRelativeHandler,
        private UserRepository $userRepository,
    ) {
    }

    public function index(GetUserRelativeListRequest $request): JsonResponse
    {
        $userId = Uuid::fromString($request->route('id'));
        $user = $this->userRepository->getUser($userId);

        $list = $this->userRelativeService->list(
            Uuid::fromString($user->company_id),
            Uuid::fromString($user->global_company_user_id),
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(UserRelativePresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetUserRelativeRequest $request): JsonResponse
    {
        $item = $this->userRelativeService->get(Uuid::fromString($request->route('id')));

        $presenter = new UserRelativePresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateUserRelativeRequest $request): JsonResponse
    {
        $createCreateUserRelativeDTO = $request->createCreateUserRelativeDTO();
        $userId = Uuid::fromString($request->input('user_id'));

        $user = $this->userRepository->getUser($userId);
        $createCreateUserRelativeDTO->company_id = $user->company_id;
        $createCreateUserRelativeDTO->global_id = $user->global_company_user_id;


        $createdItem = $this->userRelativeService->create($createCreateUserRelativeDTO);

        $presenter = new UserRelativePresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateUserRelativeRequest $request): JsonResponse
    {
        $command = $request->createUpdateUserRelativeCommand();
        $this->updateUserRelativeHandler->handle($command);

        $item = $this->userRelativeService->get($command->getId());

        $presenter = new UserRelativePresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteUserRelativeRequest $request): JsonResponse
    {
        $this->deleteUserRelativeHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
