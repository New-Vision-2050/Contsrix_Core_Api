<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Services;

use Illuminate\Support\Facades\Validator;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Company\CompanyCore\Repositories\CompanyRepository;
use Carbon\Carbon;
use Modules\Company\CompanyCore\Presenters\CompanyWidgetPresenter;
use Modules\CompanyUser\Repositories\CompanyUserRepository;
use Modules\UserInfo\EmploymentContract\Repositories\EmploymentContractRepository;

class CompanyUserWidgetService
{
    protected $repository;

    public function __construct(
       private CompanyUserRepository $companyUserRepository,
       private EmploymentContractRepository $employmentContractRepository
    )
    {
    }


    public function getCompanyStatistics($companyId, $globalId)
    {
      return  $this->employmentContractRepository->getEmploymentContract($companyId, $globalId);

    }
}
