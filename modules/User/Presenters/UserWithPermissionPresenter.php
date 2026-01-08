<?php

declare(strict_types=1);

namespace Modules\User\Presenters;

use Modules\RoleAndPermission\Presenters\PermissionPresenter;
use Modules\RoleAndPermission\Presenters\RolePresenter;
use Modules\RoleAndPermission\Presenters\RoleSimplePresenter;
use Modules\User\Models\User;
use BasePackage\Shared\Presenters\AbstractPresenter;

class UserWithPermissionPresenter extends AbstractPresenter
{
    private User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->user->id,
            'name' => $this->user->name,
            'email' => $this->user->email,
            'fcm_token'=> $this->user->fcm_token,
            'is_super_admin' => $this->user->hasRole("super-admin")||$this->user->is_owner?1:0,
            'phone' => $this->user->phone,
            'management_hierarchy_id' => $this->user->management_hierarchy_id ,
            "branch_id"=>$this->user->managementHierarchy?->detail?->branch_id,
            "roles"=>RoleSimplePresenter::collection($this->user->roles),
            "permissions"=>PermissionPresenter::collection($this->user->getAllPermissions()),
            "is_central_company"=>tenant("is_central_company"),
            "residence"=>$this->user->companyUser?->residence,
            "user_types"=>$this->user->companyUserCompanies->map(function($companyUserCompany) {
                return [
                    'id' => $companyUserCompany->id,
                    'company_id' => $companyUserCompany->company_id,
                    'global_company_user_id' => $companyUserCompany->global_company_user_id,
                    'role' => $companyUserCompany->getRawOriginal('role'),
                    'status' => $companyUserCompany->status,
                ];
            })
        ];
    }
}
