<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Modules\CompanyUser\Exports\ClientExport;
use Modules\Company\CompanyCore\Traits\PreDeclareComapnyAndBranchDependOnReqeuest;
use Modules\CompanyUser\Enum\CompanyUserRole;
use Modules\CompanyUser\Handlers\DeleteCompanyUserHandler;
use Modules\CompanyUser\Handlers\DeleteUserRoleHandler;
use Modules\CompanyUser\Handlers\UpdateCompanyUserHandler;
use Modules\CompanyUser\Presenters\ClientPresenter;
use Modules\CompanyUser\Presenters\CompanyUserPresenter;
use Modules\CompanyUser\Presenters\DashboardWidgetsPresenter;

use Modules\CompanyUser\Requests\Broker\CreateBrokerRequest;
use Modules\CompanyUser\Requests\Broker\GetBrokerRequest;
use Modules\CompanyUser\Requests\Client\CreateClientCompanyRequest;
use Modules\CompanyUser\Requests\Client\CreateClientRequest;
use Modules\CompanyUser\Requests\Client\GetClientRequest;
use Modules\CompanyUser\Requests\Client\ExportClientRequest;
use Modules\CompanyUser\Requests\Client\UpdateClientRequest;
use Modules\CompanyUser\Requests\DeleteUserRoleRequest;
use Modules\CompanyUser\Services\Broker\BrokerCRUDService;
use Modules\CompanyUser\Services\Client\ClientCRUDService;
use Modules\CompanyUser\Services\CompanyUserCRUDService;
use Modules\CompanyUser\Services\DashboardWidgetsService;
use Modules\User\Models\User;
use Modules\User\Presenters\UserPresenter;
use Modules\User\Presenters\UserRolesPresenter;
use Ramsey\Uuid\Uuid;

class ClientController extends Controller
{
    use PreDeclareComapnyAndBranchDependOnReqeuest;
    public function __construct(
        private ClientCRUDService $clientCRUDService,
        private UpdateCompanyUserHandler $updateCompanyUserHandler,
        private DeleteCompanyUserHandler $deleteCompanyUserHandler,
        private DeleteUserRoleHandler $deleteUserRoleHandler,
        private DashboardWidgetsService $dashboardWidgetsService,
    ) {
    }

    public function index(GetClientRequest $request): JsonResponse
    {
        $list = $this->clientCRUDService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10),
        );


        return Json::items(ClientPresenter::collection($list['data'], CompanyUserRole::CLIENT->value), paginationSettings: $list['pagination']);
    }


    public function show(GetClientRequest $request): JsonResponse
    {
        $client = $this->clientCRUDService->show($request->route('id'));
        return Json::item((new ClientPresenter($client))->getData());

    }



    public function store(CreateClientRequest $request)
    {
        $createdItem = $this->clientCRUDService->create($request->createCreateClientDTO(), $request->createCreateCompanyUserCompanyRoleDTO(), $request->createSetUserAddressDTO());
        $presenter = new CompanyUserPresenter($createdItem);

        // Check if email was sent successfully
        $message = __('messages.company_user.created');
        $emailSent = $createdItem->email_sent ?? true;

        if (!$emailSent) {
            $message = __('messages.company_user.created_email_failed');
        }

        return Json::item($presenter->getData(), message: $message);
    }


    public function createClientCompany(CreateClientCompanyRequest $request)
    {
        $createdItem = $this->clientCRUDService->createClientCompany($request->createClientCompanyDTO());
        $presenter = new UserPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    /**
     * Get all dashboard widgets data
     */
    public function getWidgets(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $dateRange = [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
        ];

        $widgetsData = $this->dashboardWidgetsService->getWidgetsData($companyId, $dateRange);

        $presentedData = DashboardWidgetsPresenter::presentWidgets($widgetsData);

        return Json::item($presentedData, message: __('messages.sub_entity_records.widgets_retrieved'));
    }

    /**
     * Delete client role for a specific user
     */
    public function deleteClientRole(DeleteUserRoleRequest $request)
    {
        $command = $request->createDeleteRoleCommand(CompanyUserRole::CLIENT->value);
        $this->deleteUserRoleHandler->handle($command);

        return Json::success(__('messages.company_user.deleted'));
    }

    /**
     * Export clients to Excel or CSV
     */
    public function export(ExportClientRequest $request)
    {
        $filters = $request->getFilters();
        $format = $request->get('format', 'xlsx');

        $filename = 'clients_' . date('Y-m-d_H-i-s') . '.' . $format;

        return Excel::download(
            new ClientExport($this->clientCRUDService, $filters),
            $filename
        );
    }

    public function update(UpdateClientRequest $request)
    {
        $updatedItem = $this->clientCRUDService->update( $request->createUpdateClientDTO(), $request->createSetUserAddressDTO());
        $presenter = new UserPresenter($updatedItem);

        return Json::item($presenter->getData());
    }


}
