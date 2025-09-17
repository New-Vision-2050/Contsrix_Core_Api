<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Presenters;

use Modules\Company\ManagementHierarchy\Presenters\ManagementHierarchyPresenter;
use Modules\CompanyUser\Enum\CompanyUserRole;
use Modules\User\Models\User;
use BasePackage\Shared\Presenters\AbstractPresenter;

class BrokerPresenter extends AbstractPresenter
{
    private User $user;
    private $role;

    public function __construct(User $user,$role = CompanyUserRole::BROKER->value)
    {
        $this->user = $user;
        $this->role = $role;
    }

    protected function present(bool $isListing = false): array
    {
        $status = $this->user->companyUserCompanies->filter(function($item) {
            return $item->getAttributes()['role'] == CompanyUserRole::BROKER->value;
        })->first()->status ??"نشط" ;
        return [
            'id' => $this->user->id,
            'name' => $this->user->name,
            'email' => $this->user->email,
            'phone' => $this->user->phone,
            "branches"=>ManagementHierarchyPresenter::collection($this->user->managementHierarchies($this->role)->get()),
            "status" => $status  == "نشط" ? 1:0,
            "type"=>$this->user->brokerDetail?->type,
            "residence"=>$this->user?->companyUser?->residence,
            "company_representative_name"=>$this->user->brokerDetail?->company_representative_name,
            "registration_number"=>$this->user->brokerDetail?->registration_number,
            "company_name"=>$this->user?->brokerDetail?->company_id !=null?$this->user->brokerDetail?->company?->name:null,
        ];
    }
}

