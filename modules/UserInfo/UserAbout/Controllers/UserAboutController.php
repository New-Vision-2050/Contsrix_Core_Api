<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserAbout\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\User\Repositories\UserRepository;
use Modules\UserInfo\UserAbout\Handlers\DeleteUserAboutHandler;
use Modules\UserInfo\UserAbout\Handlers\UpdateUserAboutHandler;
use Modules\UserInfo\UserAbout\Presenters\UserAboutPresenter;
use Modules\UserInfo\UserAbout\Requests\CreateUserAboutRequest;
use Modules\UserInfo\UserAbout\Requests\DeleteUserAboutRequest;
use Modules\UserInfo\UserAbout\Requests\GetUserAboutListRequest;
use Modules\UserInfo\UserAbout\Requests\GetUserAboutRequest;
use Modules\UserInfo\UserAbout\Requests\UpdateUserAboutRequest;
use Modules\UserInfo\UserAbout\Services\UserAboutCRUDService;
use Ramsey\Uuid\Uuid;

class UserAboutController extends Controller
{
    public function __construct(
        private UserAboutCRUDService $userAboutService,
        private UserRepository $userRepository

    ) {
    }

    public function index(GetUserAboutRequest $request): JsonResponse
    {
        $userId = Uuid::fromString($request->route('id'));

        $user = $this->userRepository->getUser($userId);

        $item = $this->userAboutService->get(
            Uuid::fromString($user->company_id),
            Uuid::fromString($user->global_company_user_id),
        );
        $presenter = new UserAboutPresenter($item);

        return Json::item($presenter->getData());
    }

    public function show(GetUserAboutRequest $request): JsonResponse
    {
        $item = $this->userAboutService->get(Uuid::fromString($request->route('id')));

        $presenter = new UserAboutPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateUserAboutRequest $request): JsonResponse
    {
        $createCreateUserAboutDTO = $request->createCreateUserAboutDTO();
        $userId = Uuid::fromString($request->input('user_id'));

         $user = $this->userRepository->getUser($userId);
        $createCreateUserAboutDTO->global_id = $user->global_company_user_id;
        $createCreateUserAboutDTO->company_id = $user->company_id;

        $createdItem = $this->userAboutService->create($createCreateUserAboutDTO);

        $presenter = new UserAboutPresenter($createdItem);

        return Json::item($presenter->getData());
    }

}
