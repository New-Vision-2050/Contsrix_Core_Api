<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserExperience\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\User\Repositories\UserRepository;
use Modules\UserInfo\UserExperience\Handlers\DeleteUserExperienceHandler;
use Modules\UserInfo\UserExperience\Handlers\UpdateUserExperienceHandler;
use Modules\UserInfo\UserExperience\Presenters\UserExperiencePresenter;
use Modules\UserInfo\UserExperience\Requests\CreateUserExperienceRequest;
use Modules\UserInfo\UserExperience\Requests\DeleteUserExperienceRequest;
use Modules\UserInfo\UserExperience\Requests\GetUserExperienceListRequest;
use Modules\UserInfo\UserExperience\Requests\GetUserExperienceRequest;
use Modules\UserInfo\UserExperience\Requests\UpdateUserExperienceRequest;
use Modules\UserInfo\UserExperience\Services\UserExperienceCRUDService;
use Ramsey\Uuid\Uuid;

class UserExperienceController extends Controller
{
    public function __construct(
        private UserExperienceCRUDService $userExperienceService,
        private UpdateUserExperienceHandler $updateUserExperienceHandler,
        private DeleteUserExperienceHandler $deleteUserExperienceHandler,
        private UserRepository $userRepository

    ) {
    }

    public function index(GetUserExperienceListRequest $request): JsonResponse
    {
        $userId = Uuid::fromString($request->route('id'));

        $user = $this->userRepository->getUser($userId);

        $list = $this->userExperienceService->list(
            Uuid::fromString($user->company_id),
            Uuid::fromString($user->global_company_user_id),
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(UserExperiencePresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetUserExperienceRequest $request): JsonResponse
    {
        $item = $this->userExperienceService->get(Uuid::fromString($request->route('id')));

        $presenter = new UserExperiencePresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateUserExperienceRequest $request): JsonResponse
    {
        $createCreateUserExperienceDTO = $request->createCreateUserExperienceDTO();
        $userId = Uuid::fromString($request->input('user_id'));

         $user = $this->userRepository->getUser($userId);
        $createCreateUserExperienceDTO->global_id = $user->global_company_user_id;
        $createCreateUserExperienceDTO->company_id = $user->company_id;


        $createdItem = $this->userExperienceService->create($createCreateUserExperienceDTO);

        $presenter = new UserExperiencePresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateUserExperienceRequest $request): JsonResponse
    {
        $command = $request->createUpdateUserExperienceCommand();
        $this->updateUserExperienceHandler->handle($command);

        $item = $this->userExperienceService->get($command->getId());

        $presenter = new UserExperiencePresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteUserExperienceRequest $request): JsonResponse
    {
        $this->deleteUserExperienceHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
