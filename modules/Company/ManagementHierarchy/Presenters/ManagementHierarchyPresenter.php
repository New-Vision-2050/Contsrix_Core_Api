<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Presenters;

use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\User\Models\User;

class ManagementHierarchyPresenter extends AbstractPresenter
{
    private ManagementHierarchy $managementHierarchy;

    public function __construct(ManagementHierarchy $managementHierarchy)
    {
        $this->managementHierarchy = $managementHierarchy;
    }

    protected function present(bool $isListing = false): array
    {
        $descendants=ManagementHierarchy::query()->whereSelfOrDescendantOf($this->managementHierarchy)->where("company_id",$this->managementHierarchy->company_id)->get();
        $users = $this->managementHierarchy->users?->where("company_id",$this->managementHierarchy->company_id);
        return [
            'id' => $this->managementHierarchy->id,
            'parent_id' => $this->managementHierarchy->parent_id,
            'name' => $this->managementHierarchy->name,
            'type' => $this->managementHierarchy->type,
            'phone' => $this->managementHierarchy->phone,
            'phone_code' => $this->managementHierarchy->phone_code,
            'email' => $this->managementHierarchy->email,
            "manager_id" => $this->managementHierarchy->manager_id,
            "manager"=>[
                "id"=>$this->managementHierarchy->user?->id,
                "name"=>$this->managementHierarchy->user?->name,
                "email"=>$this->managementHierarchy->user?->email,
                "phone"=>$this->managementHierarchy->user?->phone,
            ],
            'latitude' => $this->managementHierarchy->lattitude,
            'longitude' => $this->managementHierarchy->longitude,
            'country_id' => $this->managementHierarchy->address?->country_id,
            'state_id' => $this->managementHierarchy->address?->state_id,
            'city_id' => $this->managementHierarchy->address?->city_id,
            'country_name' => $this->managementHierarchy->address?->country?->name,
            'state_name' => $this->managementHierarchy->address?->state?->name,
            'city_name' => $this->managementHierarchy->address?->city?->name,
            "department_count"=>$descendants->where("type","department")->count(),
            "management_count"=>$descendants->where("type","management")->count(),
            "branch_count"=>$descendants->where("type","branch")->count()-1,//because it counts him self
            "user_count"=>$users->count()


            //example of nested structure
//            'user' => $this->managementHierarchy->users,
        ];
    }
}
