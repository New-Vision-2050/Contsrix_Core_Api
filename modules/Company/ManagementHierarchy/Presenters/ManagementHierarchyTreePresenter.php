<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Presenters;

use App\Traits\CalculateTreeManagementHierarchy;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\User\Models\User;
use Modules\User\Presenters\UserPresenter;

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
            'deputy_managers' => $this->managementHierarchy->detail?->deputyManagers&&count($this->managementHierarchy->detail?->deputyManagers)>0?DeputyManagerPresenter::collection($this->managementHierarchy->detail?->deputyManagers):[],
            'description' => $this->managementHierarchy->detail?->decription,
            'reference_user_id' => $this->managementHierarchy->detail?->reference_user_id,
            'reference_user' => $this->managementHierarchy->detail?->referanceUser ? (new UserPresenter($this->managementHierarchy->detail?->referanceUser))->getData():null,


            "manager_id" => $this->managementHierarchy->manager_id,
            "manager" => [
                "id" => $this->managementHierarchy->user?->id,
                "name" => $this->managementHierarchy->user?->name,
                "email" => $this->managementHierarchy->user?->email,
                "phone" => $this->managementHierarchy->user?->phone,
                "photo"=>$this->managementHierarchy->user?->companyUser->getFirstMedia('upload_user')?->getFullUrl()
            ],
            "deputy_manager" => [//TODO those are multiple users
                "id" => null,
                "name" =>null,
                "email" => null,
                "phone" => null,
                "photo"=>null
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
