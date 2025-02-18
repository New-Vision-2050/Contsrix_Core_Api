<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\CompanyUser\Enum\CompanyUserRole;
use Modules\CompanyUser\Events\UserUpdated;
use Modules\CompanyUser\Handlers\AssignRoleCompanyUserHandler;
use Modules\CompanyUser\Handlers\DeleteCompanyUserHandler;
use Modules\CompanyUser\Handlers\DeleteCompanyUserRoleHandler;
use Modules\CompanyUser\Handlers\UpdateCompanyUserHandler;
use Modules\CompanyUser\Models\CompanyUser;
use Modules\CompanyUser\Presenters\CompanyUserPresenter;
use Modules\CompanyUser\Presenters\WidgetCompanyUserPresenter;
use Modules\CompanyUser\Requests\AssignRoleCompanyUserRequest;
use Modules\CompanyUser\Requests\CreateCompanyUserRequest;
use Modules\CompanyUser\Requests\DeleteCompanyUserRequest;
use Modules\CompanyUser\Requests\DeleteCompanyUserSpecificRoleRequest;
use Modules\CompanyUser\Requests\GetCompanyUserListRequest;
use Modules\CompanyUser\Requests\GetCompanyUserRequest;
use Modules\CompanyUser\Requests\UpdateCompanyUserRequest;
use Modules\CompanyUser\Services\CompanyUserCRUDService;
use Modules\CompanyUser\Services\CompanyUserValidationService;
use Modules\CompanyUser\Services\CompanyUserWidgetsService;
use Ramsey\Uuid\Uuid;

class CompanyUserController extends Controller
{
    public function __construct(
        private CompanyUserCRUDService       $companyUserService,
        private CompanyUserWidgetsService    $companyUserWidgetService,
        private CompanyUserValidationService $companyUserValidationService,
        private UpdateCompanyUserHandler     $updateCompanyUserHandler,
        private AssignRoleCompanyUserHandler $assignRoleCompanyUserHandler,
        private DeleteCompanyUserRoleHandler $deleteCompanyUserRoleHandler,
        private DeleteCompanyUserHandler     $deleteCompanyUserHandler,
    )
    {
    }

    public function index(GetCompanyUserListRequest $request): JsonResponse
    {
        $list = $this->companyUserService->list(
            (int)$request->get('page', 1),
            (int)$request->get('per_page', 10)
        );

        return Json::item(['data' => CompanyUserPresenter::collection($list["data"]), 'pagination' => $list['pagination']]);
    }


    public function widgets()
    {
        $presenter = new WidgetCompanyUserPresenter(
            $this->companyUserWidgetService->getTotalUserWidget(),
            $this->companyUserWidgetService->getTotalLastMonthUserWidget(),
            $this->companyUserWidgetService->getTotalActiveUserWidget(),
            $this->companyUserWidgetService->getTotalInactiveUserWidget()
        );
        return Json::item($presenter->getData());
    }


    public function show(GetCompanyUserRequest $request): JsonResponse
    {
        $item = $this->companyUserService->get(Uuid::fromString($request->route('id')));

        $presenter = new CompanyUserPresenter($item);

        return Json::item($presenter->getData());
    }


    public function store(CreateCompanyUserRequest $request)
    {
        try {
            $createdItem = $this->companyUserService->create($request->createCreateCompanyUserDTO(), $request->createCreateCompanyUserCompanyRoleDTO());
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), httpStatus: $e->getCode());
        }

        $presenter = new CompanyUserPresenter($createdItem);
        return Json::item($presenter->getData());
    }


    public function assignRoleForCompanies(AssignRoleCompanyUserRequest $request)
    {
        $command = $request->createAssignCompanyUserCommand();
        $this->assignRoleCompanyUserHandler->handle($command);

        $item = $this->companyUserService->get($command->getId());

        $presenter = new CompanyUserPresenter($item);

        return Json::item($presenter->getData());
    }


    public function validation()
    {
        $validations = $this->companyUserValidationService
            ->validateName()
            ->validateEmail()
            ->validatePhone()
            ->get();
        return Json::item($validations);

    }

    public function update(UpdateCompanyUserRequest $request): JsonResponse
    {
        $command = $request->createUpdateCompanyUserCommand();
        $this->updateCompanyUserHandler->handle($command);

        $item = $this->companyUserService->get($command->getId());

        $presenter = new CompanyUserPresenter($item);

        return Json::item($presenter->getData());

    }

    public function delete(DeleteCompanyUserRequest $request): JsonResponse
    {
        try {
            $this->deleteCompanyUserHandler->handle(Uuid::fromString($request->route('id')));
        } catch (\Exception $exception) {
            return Json::error($exception->getMessage(), httpStatus: $exception->getCode());
        }

        return Json::success("Deleted successfully");
    }


    public function deleteForSpecificRole(DeleteCompanyUserSpecificRoleRequest $request): JsonResponse
    {
        try {
            $command = $request->createDeleteRoleCommand();
            $this->deleteCompanyUserRoleHandler->handle($command);
        } catch (\Exception $exception) {
            return Json::error($exception->getMessage(), httpStatus: $exception->getCode());
        }

        return Json::success("Deleted successfully");
    }


    public function roles()
    {
        return Json::item(CompanyUserRole::array());
    }

}
