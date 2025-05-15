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
    private static bool $skipManagementMainNodes = false;

    public function __construct(ManagementHierarchy $managementHierarchy)
    {
        $this->managementHierarchy = $managementHierarchy;
    }


    public static function setSkipManagementMainNodes(bool $skip): void
    {
        self::$skipManagementMainNodes = $skip;
    }


    protected function processChildren($children)
    {
        if (!self::$skipManagementMainNodes) {
            // Normal processing without skipping
            return ManagementHierarchyTreePresenter::Collection($children);
        }

        $result = [];

        foreach ($children as $child) {
            if ($child->type === 'management' && $child->is_main == 1) {
                // Skip this node but include its children in the result
                if ($child->children && $child->children->count() > 0) {
                    // Process each child of the skipped node
                    foreach ($child->children as $grandchild) {
                        $presenter = new ManagementHierarchyTreePresenter($grandchild);
                        $result[] = $presenter->getData();
                    }
                }
            } else {
                // Include this node normally
                $presenter = new ManagementHierarchyTreePresenter($child);
                $result[] = $presenter->getData();
            }
        }

        return $result;
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
            'deputy_managers' =>$this->managementHierarchy->detail?->deputyManagers&& count($this->managementHierarchy->detail?->deputyManagers)>0?UserPresenter::collection($this->managementHierarchy->detail?->deputyManagers):[],
            'description' => $this->managementHierarchy->detail?->description,
            'reference_user_id' => $this->managementHierarchy->detail?->reference_user_id,
            'branch_id' => $this->managementHierarchy->detail?->branch_id,
            "status" => $this->managementHierarchy->is_active,
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
            "children" => $this->processChildren($this->managementHierarchy->children),
//            "direct_users"=> $this->managementHierarchy->directUserChildren

        ];
    }
}
