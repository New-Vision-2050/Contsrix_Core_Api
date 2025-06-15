<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Services;

use Illuminate\Support\Facades\Validator;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Company\CompanyCore\Repositories\CompanyRepository;
use Carbon\Carbon;
use Modules\Company\CompanyCore\Presenters\CompanyWidgetPresenter;
use Modules\CompanyUser\Presenters\WidgetCompanyUserProfilePresenter;
use Modules\CompanyUser\Repositories\CompanyUserRepository;
use Modules\UserInfo\EmploymentContract\Repositories\EmploymentContractRepository;
use Modules\UserInfo\UserSalary\Repositories\UserSalaryRepository;

class CompanyUserWidgetService
{
    protected $repository;
    private array $employmentContract;
    private mixed $userSalary;
    public function __construct(
       private CompanyUserRepository $companyUserRepository,
       private EmploymentContractRepository $employmentContractRepository,
       private UserSalaryRepository $userSalaryRepository,

    )
    {
    }


    public function getCompanyStatistics($companyId, $globalId)
    {
        $employmentContract = $this->employmentContractRepository->getEmploymentContract($companyId, $globalId);
        $userSalary = $this->userSalaryRepository->getUserSalary($companyId, $globalId);

    return    $presenter = new WidgetCompanyUserProfilePresenter(
            $employmentContract,
            $userSalary
        );

    }
}
