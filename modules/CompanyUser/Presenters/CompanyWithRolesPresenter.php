<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Company\Models\Company;
use Modules\CompanyUser\Models\CompanyUser;

class CompanyWithRolesPresenter extends AbstractPresenter
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
            'roles' => $this->companyUser->rolesForCompany($this->company->id)
        ];
    }

}
