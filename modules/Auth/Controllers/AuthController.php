<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers;

use BasePackage\Shared\Facade\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Handlers\DeleteAuthHandler;
use Modules\Auth\Handlers\UpdateAuthHandler;
use Modules\Auth\Presenters\AuthPresenter;
use Modules\Auth\Requests\CreateAuthRequest;
use Modules\Auth\Requests\DeleteAuthRequest;
use Modules\Auth\Requests\GetAuthListRequest;
use Modules\Auth\Requests\GetAuthRequest;
use Modules\Auth\Requests\UpdateAuthRequest;
use Modules\Auth\Services\AuthCRUDService;
use Ramsey\Uuid\Uuid;

class AuthController extends Controller
{
    public function __construct(
        private AuthCRUDService $authService,
        private UpdateAuthHandler $updateAuthHandler,
        private DeleteAuthHandler $deleteAuthHandler,
    ) {
    }

    public function index(GetAuthListRequest $request): JsonResponse
    {
        $list = $this->authService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::buildItems(null,['auths' => AuthPresenter::collection($list['data']),'pagination' => $list['pagination']]);
    }

    public function show(GetAuthRequest $request): JsonResponse
    {
        $item = $this->authService->get(Uuid::fromString($request->route('id')));

        $presenter = new AuthPresenter($item);

        return Json::buildItems('auth', $presenter->getData());
    }

    public function store(CreateAuthRequest $request): JsonResponse
    {
        $createdItem = $this->authService->create($request->createCreateAuthDTO());

        $presenter = new AuthPresenter($createdItem);

        return Json::buildItems('auth', $presenter->getData());
    }

    public function update(UpdateAuthRequest $request): JsonResponse
    {
        $command = $request->createUpdateAuthCommand();
        $this->updateAuthHandler->handle($command);

        $item = $this->authService->get($command->getId());

        $presenter = new AuthPresenter($item);

        return Json::buildItems('auth', $presenter->getData());
    }

    public function delete(DeleteAuthRequest $request): JsonResponse
    {
        $this->deleteAuthHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
