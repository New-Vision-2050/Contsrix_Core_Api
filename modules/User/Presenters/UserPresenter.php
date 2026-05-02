<?php

declare(strict_types=1);

namespace Modules\User\Presenters;

use Modules\Company\ManagementHierarchy\Presenters\ManagementHierarchyPresenter;
use Modules\Company\ManagementHierarchy\Presenters\ManagementHierarchySimpleDataPresenter;
use Modules\RoleAndPermission\Presenters\PermissionPresenter;
use Modules\RoleAndPermission\Presenters\RolePresenter;
use Modules\RoleAndPermission\Presenters\RoleSimplePresenter;
use Modules\User\Models\User;
use BasePackage\Shared\Presenters\AbstractPresenter;

class UserPresenter extends AbstractPresenter
{
    private User $user;
    private ?int $filterRole;

    public function __construct(User $user, ?int $filterRole = null)
    {
        $this->user = $user;
        $this->filterRole = $filterRole;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->user->id,
            'name' => $this->user->name,
            'email' => $this->user->email,
            'is_super_admin' => $this->user->hasRole("super-admin") || $this->user->is_owner ? 1 : 0,
            'phone' => $this->user->phone,
            'phone_code' => $this->user->phone_code,
            "job_title_id" => $this->user->userProfessionalData?->job_title_id,
            'management_hierarchy_id' => $this->user->management_hierarchy_id,
            "branch_id" => $this->user->managementHierarchy?->detail?->branch_id,
            "roles" => RoleSimplePresenter::collection($this->user->roles),
            "branches" => ManagementHierarchySimpleDataPresenter::collection($this->user->managementHierarchies(request()->role)->get()),
            "user_types" => $this->user->companyUserCompanies
               
                ->map(function ($companyUserCompany) {
                    return [
                        'id' => $companyUserCompany->id,
                        'company_id' => $companyUserCompany->company_id,
                        'global_company_user_id' => $companyUserCompany->global_company_user_id,
                        'role' => $companyUserCompany->getRawOriginal('role'),
                        'status' => $companyUserCompany->status,
                    ];
                }),
            "status" => $this->filterRole !== null
                ? ($this->user->companyUserCompanies->first(
                    fn($c) => (int) $c->getRawOriginal('role') === $this->filterRole
                  )?->getRawOriginal('status') ?? $this->user->status)
                : $this->user->status,

//            "permissions"=>PermissionPresenter::collection($this->user->getAllPermissions()),
            "is_central_company" => tenant("is_central_company"),
            "residence" => $this->user->companyUser?->residence,
            "identity" => $this->user->companyUser?->identity,
            "country_id" => $this->user->companyUser?->country_id,
        ];
    }
}
