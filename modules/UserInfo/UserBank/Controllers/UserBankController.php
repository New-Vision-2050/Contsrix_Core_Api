<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserBank\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\UserInfo\UserBank\Handlers\DeleteUserBankHandler;
use Modules\UserInfo\UserBank\Handlers\UpdateUserBankHandler;
use Modules\UserInfo\UserBank\Presenters\UserBankPresenter;
use Modules\UserInfo\UserBank\Requests\CreateUserBankRequest;
use Modules\UserInfo\UserBank\Requests\DeleteUserBankRequest;
use Modules\UserInfo\UserBank\Requests\GetUserBankListRequest;
use Modules\UserInfo\UserBank\Requests\GetUserBankRequest;
use Modules\UserInfo\UserBank\Requests\UpdateUserBankRequest;
use Modules\UserInfo\UserBank\Services\UserBankCRUDService;
use Ramsey\Uuid\Uuid;

class UserBankController extends Controller
{
    public function __construct(
        private UserBankCRUDService $userBankService,
        private UpdateUserBankHandler $updateUserBankHandler,
        private DeleteUserBankHandler $deleteUserBankHandler,
    ) {
    }

    public function index(GetUserBankListRequest $request): JsonResponse
    {
        $list = $this->userBankService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(UserBankPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetUserBankRequest $request): JsonResponse
    {
        $item = $this->userBankService->get(Uuid::fromString($request->route('id')));

        $presenter = new UserBankPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateUserBankRequest $request): JsonResponse
    {
        $createdItem = $this->userBankService->create($request->createCreateUserBankDTO());

        $presenter = new UserBankPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateUserBankRequest $request): JsonResponse
    {
        $command = $request->createUpdateUserBankCommand();
        $this->updateUserBankHandler->handle($command);

        $item = $this->userBankService->get($command->getId());

        $presenter = new UserBankPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteUserBankRequest $request): JsonResponse
    {
        $this->deleteUserBankHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
