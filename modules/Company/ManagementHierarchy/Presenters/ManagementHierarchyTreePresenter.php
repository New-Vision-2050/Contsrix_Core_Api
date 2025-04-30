<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Presenters;

use App\Traits\CalculateTreeManagementHierarchy;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\User\Models\User;

class ManagementHierarchyTreePresenter extends AbstractPresenter
{
    use CalculateTreeManagementHierarchy;
    private ManagementHierarchy $managementHierarchy;

    public function __construct(ManagementHierarchy $managementHierarchy)
    {
        $this->managementHierarchy = $managementHierarchy;
    }



    protected function present(bool $isListing = false): array
    {
        //Theta(n+1)
//        $descendants = ManagementHierarchy::query()->whereSelfOrDescendantOf($this->managementHierarchy)->where("company_id", $this->managementHierarchy->company_id)->get();
       [$branchCount , $managementCount , $departmentCount] = $this->calculateHierarchyCounts($this->managementHierarchy->children);

        $users = $this->managementHierarchy->users?->where("company_id", $this->managementHierarchy->company_id);//theta (1)
        return [
            'id' => $this->managementHierarchy->id,
            'parent_id' => $this->managementHierarchy->parent_id,
            'name' => $this->managementHierarchy->name,
            'type' => $this->managementHierarchy->type,

            "manager_id" => $this->managementHierarchy->manager_id,
            "manager" => [
                "id" => $this->managementHierarchy->user?->id,
                "name" => $this->managementHierarchy->user?->name,
                "email" => $this->managementHierarchy->user?->email,
                "phone" => $this->managementHierarchy->user?->phone,
            ],
            "department_count" => $departmentCount,//because it counts him self,
            "management_count" =>$managementCount,//because it counts him self
            "branch_count" =>$branchCount,//because it counts him self
            "user_count" => $users?->count(),
            "children" => ManagementHierarchyTreePresenter::collection($this->managementHierarchy->children),
//            "direct_users"=> $this->managementHierarchy->directUserChildren

        ];
    }
}
