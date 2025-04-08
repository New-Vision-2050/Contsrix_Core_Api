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
use Modules\User\Models\User;
use Ramsey\Uuid\Uuid;

class CompanyUserProfileController extends Controller
{
    public function __construct(
        private CompanyUserCRUDService           $companyUserService,
    )
    {
    }

    public function profile(): JsonResponse
    {
        $user = $this->companyUserService->get(
            Uuid::fromString(auth()->user()->global_company_user_id) ,
        );

        $presenter = new CompanyUserPresenter($user);

        return Json::item($presenter->getData());
    }


}
