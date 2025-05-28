<?php

declare(strict_types=1);

namespace Modules\User\Presenters;

use Modules\Company\ManagementHierarchy\Presenters\ManagementHierarchyPresenter;
use Modules\Company\ManagementHierarchy\Presenters\ManagementHierarchySimpleDataPresenter;
use Modules\CompanyUser\Enum\CompanyUserRole;
use Modules\CompanyUser\Models\CompanyUserCompany;
use Modules\User\Models\User;
use BasePackage\Shared\Presenters\AbstractPresenter;

class UserBranchesPresenter extends AbstractPresenter
{
    private ?CompanyUserCompany $user;

    public function __construct(?CompanyUserCompany $user)
    {
        $this->user = $user;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'role' => $this->user?->role,
            "branches"=>$this->user?->managementHierarchy?ManagementHierarchySimpleDataPresenter::collection($this->user->managementHierarchy):[]
        ];
    }
}
