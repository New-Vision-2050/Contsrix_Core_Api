<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Company\Models\Company;
use Modules\CompanyUser\Enum\CompanyUserRole;
use Modules\CompanyUser\Enum\CompanyUserStatus;
use Modules\CompanyUser\Models\CompanyUser;
use Modules\CompanyUser\Models\CompanyUserCompany;

class RolesPresenter extends AbstractPresenter
{

    private  $company;

    public function __construct( $companyUserCompany)
    {
        $this->company = $companyUserCompany;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'role' => CompanyUserRole::lang($this->company->role),
            'status' => CompanyUserStatus::lang($this->company->status)
        ];
    }

}
