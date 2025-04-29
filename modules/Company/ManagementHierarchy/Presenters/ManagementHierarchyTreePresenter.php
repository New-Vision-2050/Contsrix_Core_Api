<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Presenters;

use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\User\Models\User;

class ManagementHierarchyTreePresenter extends AbstractPresenter
{
    private ManagementHierarchy $managementHierarchy;

    public function __construct(ManagementHierarchy $managementHierarchy)
    {
        $this->managementHierarchy = $managementHierarchy;
    }

    protected function present(bool $isListing = false): array
    {
        //Theta(n+1)
        $descendants=ManagementHierarchy::query()->whereSelfOrDescendantOf($this->managementHierarchy)->where("company_id",$this->managementHierarchy->company_id)->get();
        $users = $this->managementHierarchy->users?->where("company_id",$this->managementHierarchy->company_id);//theta (1)
        return [
            'id' => $this->managementHierarchy->id,
            'parent_id' => $this->managementHierarchy->parent_id,
            'name' => $this->managementHierarchy->name,
            'type' => $this->managementHierarchy->type,

            "manager_id" => $this->managementHierarchy->manager_id,
            "manager"=>[
                "id"=>$this->managementHierarchy->user?->id,
                "name"=>$this->managementHierarchy->user?->name,
                "email"=>$this->managementHierarchy->user?->email,
                "phone"=>$this->managementHierarchy->user?->phone,
            ],
            "department_count"=>$this->managementHierarchy->type == "department"? $descendants?->where("type","department")?->count()-1:$descendants?->where("type","department")?->count(),//because it counts him self,
            "management_count"=>$this->managementHierarchy->type == "management"? $descendants?->where("type","management")?->count()-1 : $descendants?->where("type","management")?->count(),//because it counts him self
            "branch_count"=>$this->managementHierarchy->type == "branch"? $descendants?->where("type","branch")?->count()-1:$descendants?->where("type","branch")?->count(),//because it counts him self
            "user_count"=>$users?->count(),
            "children"=>ManagementHierarchyTreePresenter::collection($this->managementHierarchy->children)

        ];
    }
}
