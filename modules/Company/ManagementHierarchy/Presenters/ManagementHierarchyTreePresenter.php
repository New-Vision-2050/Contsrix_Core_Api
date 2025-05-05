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
        $hierarchyCounts = $this->managementHierarchy->getCachedHierarchyCounts()
            ?? $this->managementHierarchy->cacheHierarchyCounts();
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
                "photo"=>$this->managementHierarchy->user?->companyUser->getFirstMedia('upload_user')?->getFullUrl()
            ],
            "deputy_manager" => [
                "id" => $this->managementHierarchy->detail?->user?->id,
                "name" => $this->managementHierarchy->detail?->user?->name,
                "email" => $this->managementHierarchy->detail?->user?->email,
                "phone" => $this->managementHierarchy->detail?->user?->phone,
                "photo"=>$this->managementHierarchy->user?->companyUser->getFirstMedia('upload_user')?->getFullUrl()
            ],
            "department_count" => $hierarchyCounts['department_count'],
            "management_count" => $hierarchyCounts['management_count'],
            "branch_count" => $hierarchyCounts['branch_count'],
            "user_count" => $users?->count(),
            "children" => ManagementHierarchyTreePresenter::collection($this->managementHierarchy->children),
//            "direct_users"=> $this->managementHierarchy->directUserChildren

        ];
    }
}
