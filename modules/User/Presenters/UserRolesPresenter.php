<?php

declare(strict_types=1);

namespace Modules\User\Presenters;

use Modules\Company\ManagementHierarchy\Presenters\ManagementHierarchyPresenter;
use Modules\CompanyUser\Enum\CompanyUserRole;
use Modules\User\Models\User;
use BasePackage\Shared\Presenters\AbstractPresenter;

class UserRolesPresenter extends AbstractPresenter
{
    private User $user;
    private $role;

    public function __construct(User $user,$role = CompanyUserRole::EMPLOYEE->value)
    {
        $this->user = $user;
        $this->role = $role;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->user->id,
            'name' => $this->user->name,
            'email' => $this->user->email,
            'phone' => $this->user->phone,
            "branches"=>ManagementHierarchyPresenter::collection($this->user->managementHierarchies($this->role)->get()),
            "status"=>$this->user->status,
            "client_data"=>$this->user->clientDetail
        ];
    }
}
