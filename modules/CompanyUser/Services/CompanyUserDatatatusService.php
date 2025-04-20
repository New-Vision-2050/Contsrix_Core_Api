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
use Modules\UserInfo\Biography\Repositories\BiographyRepository;
use Modules\UserInfo\EmploymentContract\Repositories\EmploymentContractRepository;
use Modules\UserInfo\UserAbout\Repositories\UserAboutRepository;
use Modules\UserInfo\UserSalary\Models\UserSalary;
use Modules\UserInfo\UserSalary\Repositories\UserSalaryRepository;

class CompanyUserDatatatusService
{
    protected $repository;

    public function __construct(
       private CompanyUserRepository $companyUserRepository,
       private EmploymentContractRepository $employmentContractRepository,
       private BankAccountRepository $bankAccountRepository,
       private UserSalaryRepository $userSalaryRepository,
       private UserAboutRepository $userAboutRepository,
    )
    {
    }


    public function getDatatatus($companyId, $globalId)//: array
    {
        $companyUser =  $this->companyUserRepository->getCompanyUserGlobalId($globalId);
        $employmentContract = $this->employmentContractRepository->getEmploymentContract($companyId, $globalId);
        $userSalary = $this->userSalaryRepository->getUserSalary($companyId, $globalId);
        $bankAccounts = $this->bankAccountRepository->getBankAccountList($companyId, $globalId, 1);
        $userAbout = $this->userAboutRepository->getUserAbout($companyId, $globalId);


        return [
            'employment_contract' => !empty($employmentContract),
            'user_salary' => !empty($userSalary),
            'bank_accounts' => isset($bankAccounts['data']) && count($bankAccounts['data']) > 0,
            'user_about' => !empty($userAbout)
        ];
    }
}
