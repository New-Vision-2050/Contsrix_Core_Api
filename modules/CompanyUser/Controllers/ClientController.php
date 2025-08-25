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
use Modules\CompanyUser\Presenters\ClientPresenter;
use Modules\CompanyUser\Presenters\CompanyUserPresenter;

use Modules\CompanyUser\Requests\Broker\CreateBrokerRequest;
use Modules\CompanyUser\Requests\Broker\GetBrokerRequest;
use Modules\CompanyUser\Requests\Client\CreateClientRequest;
use Modules\CompanyUser\Services\Broker\BrokerCRUDService;
use Modules\CompanyUser\Services\Client\ClientCRUDService;
use Modules\CompanyUser\Services\CompanyUserCRUDService;
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
    ) {
    }

    public function index(GetBrokerRequest $request): JsonResponse
    {
        $list = $this->clientCRUDService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10),
        );


        return Json::items(ClientPresenter::collection($list['data'],CompanyUserRole::CLIENT->value),paginationSettings: $list['pagination']);
    }



    public function store(CreateClientRequest $request): JsonResponse
    {
        $createdItem = $this->clientCRUDService->create($request->createCreateClientDTO(), $request->createCreateCompanyUserCompanyRoleDTO(),$request->createSetUserAddressDTO());

        $presenter = new CompanyUserPresenter($createdItem);

        return Json::item($presenter->getData());
    }


}
