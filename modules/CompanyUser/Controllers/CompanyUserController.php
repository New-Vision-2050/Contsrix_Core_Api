<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\CompanyUser\Handlers\DeleteCompanyUserHandler;
use Modules\CompanyUser\Handlers\UpdateCompanyUserHandler;
use Modules\CompanyUser\Presenters\CompanyUserPresenter;
use Modules\CompanyUser\Requests\CreateCompanyUserRequest;
use Modules\CompanyUser\Requests\DeleteCompanyUserRequest;
use Modules\CompanyUser\Requests\GetCompanyUserListRequest;
use Modules\CompanyUser\Requests\GetCompanyUserRequest;
use Modules\CompanyUser\Requests\UpdateCompanyUserRequest;
use Modules\CompanyUser\Services\CompanyUserCRUDService;
use Ramsey\Uuid\Uuid;

class CompanyUserController extends Controller
{
    public function __construct(
        private CompanyUserCRUDService $companyUserService,
        private UpdateCompanyUserHandler $updateCompanyUserHandler,
        private DeleteCompanyUserHandler $deleteCompanyUserHandler,
    ) {
    }

    public function index(GetCompanyUserListRequest $request): JsonResponse
    {
        $list = $this->companyUserService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::buildItems(null,['company_users' => CompanyUserPresenter::collection($list['data']),'pagination' => $list['pagination']]);
    }

    public function show(GetCompanyUserRequest $request): JsonResponse
    {
        $item = $this->companyUserService->get(Uuid::fromString($request->route('id')));

        $presenter = new CompanyUserPresenter($item);

        return Json::buildItems('company_user', $presenter->getData());
    }

    public function store(CreateCompanyUserRequest $request): JsonResponse
    {
        $createdItem = $this->companyUserService->create($request->createCreateCompanyUserDTO());

        $presenter = new CompanyUserPresenter($createdItem);

        return Json::buildItems('company_user', $presenter->getData());
    }

    public function update(UpdateCompanyUserRequest $request): JsonResponse
    {
        $command = $request->createUpdateCompanyUserCommand();
        $this->updateCompanyUserHandler->handle($command);

        $item = $this->companyUserService->get($command->getId());

        $presenter = new CompanyUserPresenter($item);

        return Json::buildItems('company_user', $presenter->getData());
    }

    public function delete(DeleteCompanyUserRequest $request): JsonResponse
    {
        $this->deleteCompanyUserHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
