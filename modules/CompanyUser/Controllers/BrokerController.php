<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;
use Modules\CompanyUser\Exports\BrokerExport;
use Modules\Company\CompanyCore\Traits\PreDeclareComapnyAndBranchDependOnReqeuest;
use Modules\CompanyUser\Enum\CompanyUserRole;
use Modules\CompanyUser\Handlers\DeleteCompanyUserHandler;
use Modules\CompanyUser\Handlers\DeleteUserRoleHandler;
use Modules\CompanyUser\Handlers\UpdateCompanyUserHandler;
use Modules\CompanyUser\Presenters\BrokerPresenter;
use Modules\CompanyUser\Presenters\CompanyUserPresenter;

use Modules\CompanyUser\Requests\Broker\CreateBrokerRequest;
use Modules\CompanyUser\Requests\Broker\GetBrokerRequest;
use Modules\CompanyUser\Requests\Broker\ExportBrokerRequest;
use Modules\CompanyUser\Requests\Broker\UpdateBrokerRequest;
use Modules\CompanyUser\Requests\ChangeStatusRequest;
use Modules\CompanyUser\Requests\DeleteUserRoleRequest;
use Modules\CompanyUser\Services\Broker\BrokerCRUDService;
use Modules\CompanyUser\Services\CompanyUserCRUDService;
use Modules\CompanyUser\Services\BrokerDashboardWidgetsService;
use Modules\CompanyUser\Presenters\BrokerDashboardWidgetsPresenter;
use Modules\User\Models\User;
use Modules\User\Presenters\UserPresenter;
use Modules\User\Presenters\UserRolesPresenter;
use Modules\User\Services\UserCRUDService;
use Ramsey\Uuid\Uuid;

class BrokerController extends Controller
{
    use PreDeclareComapnyAndBranchDependOnReqeuest;

    public function __construct(
        private BrokerCRUDService               $brokerCRUDService,
        private UserCRUDService                 $userCRUDService,
        private UpdateCompanyUserHandler        $updateCompanyUserHandler,
        private DeleteCompanyUserHandler        $deleteCompanyUserHandler,
        private DeleteUserRoleHandler           $deleteUserRoleHandler,
        private BrokerDashboardWidgetsService   $brokerDashboardWidgetsService,
    )
    {
    }

    public function index(GetBrokerRequest $request)
    {
        $list = $this->brokerCRUDService->list(
            (int)$request->get('page', 1),
            (int)$request->get('per_page', 10),
        );


        return Json::items(BrokerPresenter::collection($list['data'], CompanyUserRole::BROKER->value), paginationSettings: $list['pagination']);
    }


    public function show(GetBrokerRequest $request): JsonResponse
    {
        $broker = $this->brokerCRUDService->show(
            $request->route('id')
        );


        return Json::item((new BrokerPresenter($broker, CompanyUserRole::BROKER->value))->getData());
    }


    public function store(CreateBrokerRequest $request)
    {
        $createdItem = $this->brokerCRUDService->create($request->createCreateBrokerDTO(), $request->createCreateCompanyUserCompanyRoleDTO(), $request->createSetUserAddressDTO());
        $presenter = new CompanyUserPresenter($createdItem);

        // Check if email was sent successfully
        $message = __('messages.company_user.created');
        $emailSent = $createdItem->email_sent ?? true;

        if (!$emailSent) {
            $message = __('messages.company_user.created_email_failed');
        }

        return Json::item($presenter->getData(), message: $message);
    }
    public function update(UpdateBrokerRequest $request)
    {
        $user = $this->brokerCRUDService->update($request->createUpdateBrokerDTO(), $request->createSetUserAddressDTO());
        $presenter = new UserPresenter($user);

        return Json::item($presenter->getData());
    }

    public function changeStatus(ChangeStatusRequest $request)
    {
        $user = $this->brokerCRUDService->changeStatus(
            $request->route('id'),
            $request->getStatus(),
        );

        $presenter = new UserPresenter($user, CompanyUserRole::BROKER->value);

        return Json::item($presenter->getData());
    }

    /**
     * Get broker dashboard widgets data
     */
    public function widgets(): JsonResponse
    {
        $widgetsData = $this->brokerDashboardWidgetsService->getWidgetsData(tenant("id"));

        $presentedData = BrokerDashboardWidgetsPresenter::presentWidgets($widgetsData);

        return Json::items($presentedData);
    }

    /**
     * Delete broker role for a specific user
     */
    public function deleteBrokerRole(DeleteUserRoleRequest $request)
    {
        $command = $request->createDeleteRoleCommand(CompanyUserRole::BROKER->value);

        $this->deleteUserRoleHandler->handle($command);

        return Json::success(__('messages.company_user.deleted'));
    }

    /**
     * Export brokers to Excel or CSV
     */
    public function export(ExportBrokerRequest $request)
    {
        $filters = $request->getFilters();
        $format = $request->get('format', 'xlsx');

        $filename = 'brokers_' . date('Y-m-d_H-i-s') . '.' . $format;

        return Excel::download(
            new BrokerExport($this->brokerCRUDService, $filters),
            $filename
        );
    }


}
