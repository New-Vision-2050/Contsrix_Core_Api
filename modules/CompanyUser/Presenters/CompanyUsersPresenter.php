<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Company\CompanyCore\Models\Company;
use Modules\CompanyUser\Models\CompanyUser;
use Modules\CompanyUser\Models\CompanyUserCompany;

class CompanyUsersPresenter extends AbstractPresenter
{
    private CompanyUser $companyUser;

    private Company $company;

    public function __construct(Company $company,CompanyUser $companyUser)
    {
        $this->company = $company;
        $this->companyUser = $companyUser;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->company->id,
            'name' => $this->company->name,
            'country_id' => $this->company->country_id,
            'roles' => RolesPresenter::collection($this->companyUser->rolesForCompany($this->company->id)),
            "phone"=>$this->companyUser->users()->where("company_id", $this->company->id)->first()?->phone,
            "logo"=> $this->company->getFirstMedia("logo")?->getFullUrl(),
            'users' => $this->companyUser->users->where('company_id', $this->company->id)->values(),
            'serial_no' => $this->company->serial_no ,
        ];
    }

}
