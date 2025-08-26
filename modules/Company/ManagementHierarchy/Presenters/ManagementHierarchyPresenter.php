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
        // Get users efficiently without n+1 query
        $users = $this->managementHierarchy->users?->where("company_id", $this->managementHierarchy->company_id);

        // Get cached hierarchy counts or calculate and cache them if not available
        $hierarchyCounts = $this->managementHierarchy->getCachedHierarchyCounts()
            ?? $this->managementHierarchy->cacheHierarchyCounts();
        return [
            'id' => $this->managementHierarchy->id,
            'company_id' => $this->managementHierarchy->company_id,
            'parent_id' => $this->managementHierarchy->parent_id,
            'is_main' => $this->managementHierarchy->is_main,
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
            'latitude' => $this->managementHierarchy->latitude,
            'longitude' => $this->managementHierarchy->longitude,
            'country_id' => $this->managementHierarchy->address?->country_id,
            'state_id' => $this->managementHierarchy->address?->state_id,
            'city_id' => $this->managementHierarchy->address?->city_id,
            'country_name' => $this->managementHierarchy->address?->country?->name,
            'state_name' => $this->managementHierarchy->address?->state?->name,
            'city_name' => $this->managementHierarchy->address?->city?->name,
            "department_count" => $hierarchyCounts['department_count'],
            "management_count" => $hierarchyCounts['management_count'],
            "branch_count" => $hierarchyCounts['branch_count'],
            "user_count"=>$users?->count(),
            "users_can_access"=>$this->managementHierarchy->usersCanAccess !=null ?$this->managementHierarchy->usersCanAccess->map(function ($userCanAccess) {
                return [
                    "id"=>$userCanAccess->id,
                    "name"=>$userCanAccess->name,
                    "email"=>$userCanAccess->email,
                    "phone"=>$userCanAccess->phone,
                ];
            }):null
        ];
    }
}
