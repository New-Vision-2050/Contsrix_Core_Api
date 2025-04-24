<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserProfessionalData\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\User\Repositories\UserRepository;
use Modules\UserInfo\UserProfessionalData\Handlers\DeleteUserProfessionalDataHandler;
use Modules\UserInfo\UserProfessionalData\Handlers\UpdateUserProfessionalDataHandler;
use Modules\UserInfo\UserProfessionalData\Presenters\UserProfessionalDataPresenter;
use Modules\UserInfo\UserProfessionalData\Requests\CreateUserProfessionalDataRequest;
use Modules\UserInfo\UserProfessionalData\Requests\DeleteUserProfessionalDataRequest;
use Modules\UserInfo\UserProfessionalData\Requests\GetUserProfessionalDataListRequest;
use Modules\UserInfo\UserProfessionalData\Requests\GetUserProfessionalDataRequest;
use Modules\UserInfo\UserProfessionalData\Requests\UpdateUserProfessionalDataRequest;
use Modules\UserInfo\UserProfessionalData\Services\UserProfessionalDataCRUDService;
use Ramsey\Uuid\Uuid;

class UserProfessionalDataController extends Controller
{
    public function __construct(
        private UserProfessionalDataCRUDService $userProfessionalDataService,
        private UpdateUserProfessionalDataHandler $updateUserProfessionalDataHandler,
        private DeleteUserProfessionalDataHandler $deleteUserProfessionalDataHandler,
        private UserRepository $userRepository
    ) {
    }

    public function index(GetUserProfessionalDataListRequest $request): JsonResponse
    {
        $userId = Uuid::fromString($request->route('id'));
        $user = $this->userRepository->getUser($userId);

        $list = $this->userProfessionalDataService->list(
            Uuid::fromString($user->company_id),
            Uuid::fromString($user->global_company_user_id),
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(UserProfessionalDataPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetUserProfessionalDataRequest $request): JsonResponse
    {
        $item = $this->userProfessionalDataService->get(Uuid::fromString($request->route('id')));

        $presenter = new UserProfessionalDataPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateUserProfessionalDataRequest $request): JsonResponse
    {
        $createCreateUserProfessionalDataDTO = $request->createCreateUserProfessionalDataDTO();
        $userId = Uuid::fromString($request->input('user_id'));

        $user = $this->userRepository->getUser($userId);
        $createCreateUserProfessionalDataDTO->global_id = $user->global_company_user_id;
        $createCreateUserProfessionalDataDTO->company_id = $user->company_id;

        $createdItem = $this->userProfessionalDataService->create($createCreateUserProfessionalDataDTO);

        $presenter = new UserProfessionalDataPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateUserProfessionalDataRequest $request): JsonResponse
    {
        $command = $request->createUpdateUserProfessionalDataCommand();
        $this->updateUserProfessionalDataHandler->handle($command);

        $item = $this->userProfessionalDataService->get($command->getId());

        $presenter = new UserProfessionalDataPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteUserProfessionalDataRequest $request): JsonResponse
    {
        $this->deleteUserProfessionalDataHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
