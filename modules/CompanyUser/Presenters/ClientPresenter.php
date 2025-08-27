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
        $status = $this->user->companyUserCompanies->filter(function($item) {
            return $item->getAttributes()['role'] == CompanyUserRole::CLIENT->value;
        })->first()->status ??"نشط" ;
        return [
            'id' => $this->user->id,
            'name' => $this->user->name,
            'email' => $this->user->email,
            'phone' => $this->user->phone,
            "branches"=>ManagementHierarchyPresenter::collection($this->user->managementHierarchies($this->role)->get()),
            "status" => $status  == "نشط" ? 1:0,
            "type"=>$this->user->clientDetail?->type,
            "residence"=>$this->user?->companyUser?->residence,
            "broker_id"=>$this->user->clientDetail?->broker_id,
            "broker"=>$this->user->clientDetail?->broker_id !=null?["id"=>$this->user->clientDetail?->broker?->id,"name"=>$this->user->clientDetail?->broker?->name]:null,
            "company_representative_name"=>$this->user->clientDetail?->company_representative_name,
            "registration_number"=>$this->user->clientDetail?->registration_number,
            "company_name"=>$this->user->clientDetail?->company_name,
        ];
    }
}

