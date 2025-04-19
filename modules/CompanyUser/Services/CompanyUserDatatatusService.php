<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Services;

use Illuminate\Support\Facades\Validator;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Company\CompanyCore\Repositories\CompanyRepository;
use Carbon\Carbon;
use Modules\Company\CompanyCore\Presenters\CompanyWidgetPresenter;
use Modules\CompanyUser\Repositories\CompanyUserRepository;
use Modules\UserInfo\BankAccount\Repositories\BankAccountRepository;
use Modules\UserInfo\EmploymentContract\Repositories\EmploymentContractRepository;

class CompanyUserDatatatusService
{
    protected $repository;

    public function __construct(
       private CompanyUserRepository $companyUserRepository,
       private EmploymentContractRepository $employmentContractRepository,
       private BankAccountRepository $bankAccountRepository
    )
    {
    }


    public function getDatatatus($companyId, $globalId)
    {
        $this->employmentContractRepository->getEmploymentContract($companyId, $globalId);

       return $this->bankAccountRepository->getBankAccountList($companyId, $globalId);

    }
}
