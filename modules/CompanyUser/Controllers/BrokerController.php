<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Company\CompanyCore\Traits\PreDeclareComapnyAndBranchDependOnReqeuest;
use Modules\CompanyUser\Enum\CompanyUserRole;
use Modules\CompanyUser\Handlers\DeleteCompanyUserHandler;
use Modules\CompanyUser\Handlers\UpdateCompanyUserHandler;
use Modules\CompanyUser\Presenters\CompanyUserPresenter;

use Modules\CompanyUser\Requests\Broker\CreateBrokerRequest;
use Modules\CompanyUser\Requests\Broker\GetBrokerRequest;
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
        private BrokerDashboardWidgetsService   $brokerDashboardWidgetsService,
    )
    {
    }

    public function index(GetBrokerRequest $request): JsonResponse
    {
        $list = $this->brokerCRUDService->list(
            (int)$request->get('page', 1),
            (int)$request->get('per_page', 10),
        );


        return Json::items(UserRolesPresenter::collection($list['data'], CompanyUserRole::BROKER->value), paginationSettings: $list['pagination']);
    }


    public function store(CreateBrokerRequest $request): JsonResponse
    {
        $createdItem = $this->brokerCRUDService->create($request->createCreateBrokerDTO(), $request->createCreateCompanyUserCompanyRoleDTO(), $request->createSetUserAddressDTO());

        $presenter = new CompanyUserPresenter($createdItem);

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




}
