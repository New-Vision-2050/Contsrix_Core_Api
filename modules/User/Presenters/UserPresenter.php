<?php

declare(strict_types=1);

namespace Modules\User\Presenters;

use Modules\RoleAndPermission\Presenters\PermissionPresenter;
use Modules\RoleAndPermission\Presenters\RolePresenter;
use Modules\RoleAndPermission\Presenters\RoleSimplePresenter;
use Modules\User\Models\User;
use BasePackage\Shared\Presenters\AbstractPresenter;

class UserPresenter extends AbstractPresenter
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
            'is_super_admin' => $this->user->hasRole("super-admin")||$this->user->is_owner?1:0,
            'phone' => $this->user->phone,
            'phone_code' => $this->user->phone_code,
            "job_title_id"=>$this->user->userProfessionalData?->job_title_id,
            'management_hierarchy_id' => $this->user->management_hierarchy_id ,
            "branch_id"=>$this->user->managementHierarchy?->detail?->branch_id,
            "roles"=>RoleSimplePresenter::collection($this->user->roles),
//            "permissions"=>PermissionPresenter::collection($this->user->getAllPermissions()),
            "is_central_company"=>tenant("is_central_company"),
            "residence"=>$this->user->companyUser?->residence,
            "identity"=>$this->user->companyUser?->identity,
            "country_id"=>$this->user->companyUser?->country_id,
        ];
    }
}
