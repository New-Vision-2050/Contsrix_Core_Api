<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Company\CompanyCore\Traits\PreDeclareComapnyAndBranchDependOnReqeuest;
use Modules\CompanyUser\DTO\Employee\CreateEmployeeDTO;
use Modules\CompanyUser\Enum\CompanyUserRole;
use Modules\CompanyUser\Handlers\DeleteCompanyUserHandler;
use Modules\CompanyUser\Handlers\UpdateCompanyUserHandler;
use Modules\CompanyUser\Presenters\CompanyUserPresenter;

use Modules\CompanyUser\Requests\Broker\CreateBrokerRequest;
use Modules\CompanyUser\Requests\Broker\GetBrokerRequest;
use Modules\CompanyUser\Requests\Employee\CreateEmployeeRequest;
use Modules\CompanyUser\Requests\Employee\UpdateEmployeeRequest;
use Modules\CompanyUser\Services\Broker\BrokerCRUDService;
use Modules\CompanyUser\Services\CompanyUserCRUDService;
use Modules\CompanyUser\Services\Employee\EmployeeCRUDService;
use Modules\User\Models\User;
use Modules\User\Presenters\EmployeePresenter;
use Modules\User\Presenters\UserPresenter;
use Modules\User\Presenters\UserRolesPresenter;
use Ramsey\Uuid\Uuid;

class EmployeeController extends Controller
{
    use PreDeclareComapnyAndBranchDependOnReqeuest;
    public function __construct(
        private EmployeeCRUDService $employeeCRUDService,
        private UpdateCompanyUserHandler $updateCompanyUserHandler,
        private DeleteCompanyUserHandler $deleteCompanyUserHandler,
    ) {
    }

    public function index(GetBrokerRequest $request): JsonResponse
    {
        $list = $this->employeeCRUDService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10),
        );


        return Json::items(EmployeePresenter::collection($list['data'],CompanyUserRole::EMPLOYEE->value),paginationSettings: $list['pagination']);
    }



    public function store(CreateEmployeeRequest $request)
    {
        $createdItem = $this->employeeCRUDService->create($request->createCreateEmployeeDTO(), $request->createCreateCompanyUserCompanyRoleDTO());
        $presenter = new CompanyUserPresenter($createdItem);

        // Check if email was sent successfully
        $message = __('messages.company_user.created');
        $emailSent = $createdItem->email_sent ?? true;

        if (!$emailSent) {
            $message = __('messages.company_user.created_email_failed');
        }

        return Json::item($presenter->getData(), message: $message);
    }


    public function update(UpdateEmployeeRequest $request)
    {
        $user = $this->employeeCRUDService->update($request->createUpdateEmployeeDTO());

        $presenter = new UserPresenter($user);

        return Json::item($presenter->getData());
    }


}
