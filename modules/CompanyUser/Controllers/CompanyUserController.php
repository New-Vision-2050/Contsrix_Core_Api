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
use Modules\CompanyUser\Handlers\DeleteUserRoleHandler;
use Modules\CompanyUser\Handlers\UpdateCompanyUserHandler;
use Modules\CompanyUser\Handlers\UpdateTimeZoneCompanyUserHandler;
use Modules\CompanyUser\Models\CompanyUser;
use Modules\CompanyUser\Presenters\CompanyUserPresenter;
use Modules\CompanyUser\Presenters\TimeZoneCompanyUserPresenter;
use Modules\CompanyUser\Presenters\ChartsPresenter;
use Modules\CompanyUser\Presenters\WidgetCompanyUserPresenter;
use Modules\CompanyUser\Requests\AssignRoleCompanyUserForCurrentCompanyRequest;
use Modules\CompanyUser\Requests\AssignRoleCompanyUserRequest;
use Modules\CompanyUser\Requests\CreateCompanyUserRequest;
use Modules\CompanyUser\Requests\DeleteCompanyUserRequest;
use Modules\CompanyUser\Requests\DeleteCompanyUserSpecificRoleRequest;
use Modules\CompanyUser\Requests\DeleteUserSpecificRoleRequest;
use Modules\CompanyUser\Requests\ExportCompanyUsersRequest;
use Modules\CompanyUser\Requests\GetCompanyUserListRequest;
use Modules\CompanyUser\Requests\GetCompanyUserRequest;
use Modules\CompanyUser\Requests\UpdateCompanyUserRequest;
use Modules\CompanyUser\Requests\UpdateTimeZoneCompanyUserRequest;
use Modules\CompanyUser\Services\CompanyUserCRUDService;
use Modules\CompanyUser\Services\CompanyUserValidationService;
use Modules\CompanyUser\Services\CompanyUserWidgetsService;
use Modules\User\Services\UserCRUDService;
use Ramsey\Uuid\Uuid;

class CompanyUserController extends Controller
{
    public function __construct(
        private CompanyUserCRUDService           $companyUserService,
        private CompanyUserWidgetsService        $companyUserWidgetService,
        private CompanyUserValidationService     $companyUserValidationService,
        private UpdateCompanyUserHandler         $updateCompanyUserHandler,
        private UpdateTimeZoneCompanyUserHandler $updateTimeZoneCompanyUserHandler,
        private AssignRoleCompanyUserHandler     $assignRoleCompanyUserHandler,
        private DeleteCompanyUserRoleHandler     $deleteCompanyUserRoleHandler,
        private DeleteUserRoleHandler            $deleteUserRoleHandler,
        private DeleteCompanyUserHandler         $deleteCompanyUserHandler,
        private UserCRUDService $userCRUDService
    )
    {
    }

    public function index(GetCompanyUserListRequest $request)
    {
        $list = $this->companyUserService->list(
            (int)$request->get('page', 1),
            (int)$request->get('per_page', 10)
        );

        return Json::items(CompanyUserPresenter::collection($list["data"]), paginationSettings: $list['pagination']);
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

    public function showByEmail(GetCompanyUserRequest $request) //: JsonResponse
    {
        $item = $this->companyUserService->getByEmail($request->email);
        if (!$item) {
            return Json::item(null);
        }
        $presenter = new CompanyUserPresenter($item);

        return Json::item($presenter->getData(), extraItems: ["userInCompany" => $this->userCRUDService->getUserBy(["email" => $request->email, "company_id" => tenant("id")])]);
    }

    public function store(CreateCompanyUserRequest $request)
    {
        $createdItem = $this->companyUserService->create(
            $request->createCreateCompanyUserDTO(),
            $request->createCreateCompanyUserCompanyRoleDTO()
        );
        $presenter = new CompanyUserPresenter($createdItem);

        // Check if email was sent successfully
        $message = __('messages.company_user.created');
        $emailSent = $createdItem->email_sent ?? true;

        if (!$emailSent) {
            $message = __('messages.company_user.created_email_failed');
        }

        return Json::item($presenter->getData(), message: $message);
    }


    public function assignRoleForCompanies(AssignRoleCompanyUserRequest $request)
    {
        $command = $request->createAssignCompanyUserCommand();
        $this->assignRoleCompanyUserHandler->handle($command);

        $item = $this->companyUserService->get($command->getId());

        $presenter = new CompanyUserPresenter($item);

        return Json::item($presenter->getData(), message: __('messages.company_user.role_assigned'));
    }

    public function assignRoleForCurrentCompany(AssignRoleCompanyUserForCurrentCompanyRequest $request)
    {
        $command = $request->createAssignCompanyUserForCurrentCompanyCommand();
        $this->assignRoleCompanyUserHandler->handle($command);

        $item = $this->companyUserService->get($command->getId());

        $presenter = new CompanyUserPresenter($item);

        return Json::item($presenter->getData(), message: __('messages.company_user.role_assigned'));
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

    public function checkEmail()
    {
        $validations = $this->companyUserValidationService
            ->validateEmail()
            ->get();
        return Json::item($validations);
    }

    public function update(UpdateCompanyUserRequest $request) //: JsonResponse
    {
        $command = $request->createUpdateCompanyUserCommand();

        $this->updateCompanyUserHandler->handle($command);

        $item = $this->companyUserService->get($command->getId());

        $presenter = new CompanyUserPresenter($item);

        return Json::item($presenter->getData(), message: __('messages.company_user.updated'));
    }

    public function changeTimeZone(UpdateTimeZoneCompanyUserRequest $request): JsonResponse
    {
        $command = $request->updateTimeZoneUpdateCompanyUserCommand();
        $this->updateTimeZoneCompanyUserHandler->handle($command);

        $item = $this->companyUserService->get($command->getId());

        $presenter = new TimeZoneCompanyUserPresenter($item);

        return Json::item($presenter->getData(), message: __('messages.company_user.timezone_updated'));
    }

    public function delete(DeleteCompanyUserRequest $request): JsonResponse
    {
        $this->deleteCompanyUserHandler->handle(Uuid::fromString($request->route('id')));

        return Json::success(__('messages.company_user.deleted'));
    }


    public function deleteForSpecificRole(DeleteCompanyUserSpecificRoleRequest $request): JsonResponse
    {

        $command = $request->createDeleteRoleCommand();
        $this->deleteCompanyUserRoleHandler->handle($command);

        return Json::success(__('messages.company_user.role_deleted'));
    }

    public function deleteUserSpecificRole(DeleteUserSpecificRoleRequest $request)
    {
        $command = $request->createDeleteRoleCommand();
        $this->deleteUserRoleHandler->handle($command);

        return Json::success(__('messages.company_user.role_deleted'));
    }


    public function charts(): JsonResponse
    {
        $genderData = $this->companyUserWidgetService->getGenderChart();
        $ageData = $this->companyUserWidgetService->getAgeChart();
        $jobTypeData = $this->companyUserWidgetService->getJobTypeChart();
        $visaExpirationData = $this->companyUserWidgetService->getVisaExpirationByMonthChart();
        $visaStatusData = $this->companyUserWidgetService->getVisaStatusChart();
        $contractExpirationData = $this->companyUserWidgetService->getContractExpirationByMonthChart();
        $contractStatusData = $this->companyUserWidgetService->getContractStatusChart();
        $nationalityData = $this->companyUserWidgetService->getNationalityChart();
        $maritalStatusData = $this->companyUserWidgetService->getMaritalStatusChart();
        $presenter = new ChartsPresenter(
            $genderData,
            $ageData,
            $jobTypeData,
            $visaExpirationData,
            $visaStatusData,
            $contractExpirationData,
            $contractStatusData,
            $nationalityData,
            $maritalStatusData
        );
        return Json::item($presenter->getData());
    }

    public function roles()
    {
        return Json::item(CompanyUserRole::array());
    }


    public function export(ExportCompanyUsersRequest $request)
    {
        $companyUserIds = $request->input('company_user_ids');
        $csv = $this->companyUserService->export($companyUserIds);
        $filename = 'users_export_' . now()->format('Y-m-d_H-i-s') . '.csv';

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}