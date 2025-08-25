<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Presenters;

use Modules\Company\ManagementHierarchy\Presenters\ManagementHierarchyPresenter;
use Modules\CompanyUser\Enum\CompanyUserRole;
use Modules\User\Models\User;
use BasePackage\Shared\Presenters\AbstractPresenter;

class ClientPresenter extends AbstractPresenter
{
    private User $user;
    private $role;

    public function __construct(User $user,$role = CompanyUserRole::CLIENT->value)
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
            "residence"=>$this->user->companyUser->residence,
            "type"=>$this->user->clientDetail?->type,
            "broker_id"=>$this->user->clientDetail?->broker_id,
            "company_representative_name"=>$this->user->clientDetail?->company_representative_name,
            "registration_number"=>$this->user->clientDetail?->registration_number,
            "company_name"=>$this->user->clientDetail?->company_name,
        ];
    }
}

