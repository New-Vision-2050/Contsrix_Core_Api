<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
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
        if (!$this->company) {
            return [];
        }
        return [
            'role' =>  (int) $this->company->role,
            'status' => (int) $this->company->status
        ];
    }

}
