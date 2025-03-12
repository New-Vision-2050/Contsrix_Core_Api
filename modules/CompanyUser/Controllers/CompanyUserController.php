<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\CompanyUser\Enum\CompanyUserRole;
use Modules\CompanyUser\Handlers\AssignRoleCompanyUserHandler;
use Modules\CompanyUser\Handlers\DeleteCompanyUserHandler;
use Modules\CompanyUser\Handlers\DeleteCompanyUserRoleHandler;
use Modules\CompanyUser\Handlers\UpdateCompanyUserHandler;
use Modules\CompanyUser\Handlers\UpdateTimeZoneCompanyUserHandler;
use Modules\CompanyUser\Models\CompanyUser;
use Modules\CompanyUser\Presenters\CompanyUserPresenter;
use Modules\CompanyUser\Presenters\TimeZoneCompanyUserPresenter;
use Modules\CompanyUser\Presenters\WidgetCompanyUserPresenter;
use Modules\CompanyUser\Requests\AssignRoleCompanyUserRequest;
use Modules\CompanyUser\Requests\CreateCompanyUserRequest;
use Modules\CompanyUser\Requests\DeleteCompanyUserRequest;
use Modules\CompanyUser\Requests\DeleteCompanyUserSpecificRoleRequest;
use Modules\CompanyUser\Requests\GetCompanyUserListRequest;
use Modules\CompanyUser\Requests\GetCompanyUserRequest;
use Modules\CompanyUser\Requests\UpdateCompanyUserRequest;
use Modules\CompanyUser\Requests\UpdateTimeZoneCompanyUserRequest;
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
        private UpdateTimeZoneCompanyUserHandler     $updateTimeZoneCompanyUserHandler,
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

        return Json::buildItems(null, ['data' => CompanyUserPresenter::collection($list["data"]), 'pagination' => $list['pagination']]);
    }

    public function widgets()
    {
        $presnter = new WidgetCompanyUserPresenter(
            $this->companyUserWidgetService->getTotalUserWidget(),
            $this->companyUserWidgetService->getTotalLastMonthUserWidget(),
            $this->companyUserWidgetService->getTotalActiveUserWidget(),
            $this->companyUserWidgetService->getTotalInactiveUserWidget()
        );
        return Json::buildItems('data', $presnter->getData());
    }

    public function show(GetCompanyUserRequest $request): JsonResponse
    {
        $item = $this->companyUserService->get(Uuid::fromString($request->route('id')));

        $presenter = new CompanyUserPresenter($item);

        return Json::buildItems('company_user', $presenter->getData());
    }

    public function store(CreateCompanyUserRequest $request)
    {
        try {
            $createdItem = $this->companyUserService->create(
                $request->createCreateCompanyUserDTO(),
                $request->createCreateCompanyUserCompanyRoleDTO()
            );
        } catch (\Exception $e) {
            // Ensure the status code is always an integer, defaulting to 500 if invalid
            $statusCode = is_int($e->getCode()) && $e->getCode() >= 100 && $e->getCode() < 600 ? $e->getCode() : 500;
            return Json::buildItems(data: ["msg" => $e->getMessage()], httpStatus: $statusCode);
        }

        $presenter = new CompanyUserPresenter($createdItem);
        return Json::buildItems(data: ['data' => $presenter->getData()]);
    }

    public function assignRoleForCompanies(AssignRoleCompanyUserRequest $request)
    {
        $command = $request->createAssignCompanyUserCommand();
        $this->assignRoleCompanyUserHandler->handle($command);

        $item = $this->companyUserService->get($command->getId());

        $presenter = new CompanyUserPresenter($item);

        return Json::buildItems('data', $presenter->getData());
    }

    public function validation()
    {
        $validations = $this->companyUserValidationService
            ->validateName()
            ->validateEmail()
            ->validatePhone()
            ->get();
        return Json::buildItems("validations", $validations);
    }

    public function update(UpdateCompanyUserRequest $request): JsonResponse
    {
        $command = $request->createUpdateCompanyUserCommand();
        $this->updateCompanyUserHandler->handle($command);

        $item = $this->companyUserService->get($command->getId());

        $presenter = new CompanyUserPresenter($item);

        return Json::buildItems('data', $presenter->getData());
    }
    public function changeTimeZone(UpdateTimeZoneCompanyUserRequest $request): JsonResponse
    {
        $command = $request->updateTimeZoneUpdateCompanyUserCommand();
        $this->updateTimeZoneCompanyUserHandler->handle($command);

        $item = $this->companyUserService->get($command->getId());

        $presenter = new TimeZoneCompanyUserPresenter($item);

        return Json::buildItems('data', $presenter->getData());
    }
    public function delete(DeleteCompanyUserRequest $request): JsonResponse
    {
        try {
            $this->deleteCompanyUserHandler->handle(Uuid::fromString($request->route('id')));

        } catch (\Exception $exception) {
            return Json::buildItems(data: ["msg" => $exception->getMessage()], httpStatus: $exception->getCode());
        }

        return Json::deleted();
    }

    public function deleteForSpecificRole(DeleteCompanyUserSpecificRoleRequest $request): JsonResponse
    {
        try {
            $command = $request->createDeleteRoleCommand();
            $this->deleteCompanyUserRoleHandler->handle($command);

        } catch (\Exception $exception) {
            return Json::buildItems(data: ["msg" => $exception->getMessage()], httpStatus: $exception->getCode());
        }

        return Json::deleted();
    }

    public function roles()
    {
        return Json::buildItems("data", CompanyUserRole::array());
    }
}
