<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\Company\ManagementHierarchy\Models\SourceManagementHierarchy;
use Modules\User\Presenters\UserPresenter;
use Modules\Company\ManagementHierarchy\Presenters\ManagementHierarchyPresenter;

class NonCopiedHierarchyPresenter extends AbstractPresenter
{
    private SourceManagementHierarchy $sourceManagementHierarchy;

    public function __construct(SourceManagementHierarchy $sourceManagementHierarchy)
    {
        $this->sourceManagementHierarchy = $sourceManagementHierarchy;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->sourceManagementHierarchy->id,
            'code_id' => $this->sourceManagementHierarchy->id,
            'name' => $this->sourceManagementHierarchy->name,
            'type' => $this->sourceManagementHierarchy->type,
//            'parent_id' => $this->sourceManagementHierarchy->parent_id,
//            "management"=>$this->sourceManagementHierarchy->parent?->type == "management" ? (new ManagementHierarchySimpleDataPresenter($this->sourceManagementHierarchy->parent))->getData() : null,
            "management" => $this->sourceManagementHierarchy->parent && $this->sourceManagementHierarchy->parent->type == "management" 
                ? [
                    'id' => $this->sourceManagementHierarchy->parent->id,
                    'name' => $this->sourceManagementHierarchy->parent->name,
                    'type' => $this->sourceManagementHierarchy->parent->type,
                    'is_active' => $this->sourceManagementHierarchy->parent->is_active,
                ] 
                : null,
            "departments_count"=>$this->sourceManagementHierarchy->managementHierarchies->sum(function ($clone) {
                return $clone->cacheHierarchyCounts()["department_count"]??0 ;
            }),
            "departments" => $this->getAllDepartmentsFromHierarchies(),
//            "departments"=>ManagementHierarchySimpleDataPresenter::collection($this->sourceManagementHierarchy->managementHierarchies->where("type","department")),
            'company_id' => $this->sourceManagementHierarchy->company_id,
            'is_active' => $this->sourceManagementHierarchy->is_active,
//            'is_main' => $this->sourceManagementHierarchy->is_main,
//            'manager' => $this->sourceManagementHierarchy->user ? (new UserPresenter($this->sourceManagementHierarchy->user))->present() : null,

//            'copies' => $this->sourceManagementHierarchy->clones?->map(function ($clone) {
//                return [
//                    'id' => $clone->id,
//                    'description' => $clone->description,
//                    'is_copied' => $clone->is_copied,
//                ];
//            })->toArray(),
            'users_count' => $this->sourceManagementHierarchy->managementHierarchies->sum(function ($clone) {
                return $clone->users_count ?? 0;
            })

        ];
    }

    /**
     * Get all departments from all management hierarchies trees
     */
    private function getAllDepartmentsFromHierarchies(): array
    {
        $allDepartments = [];

        // Get all management hierarchies related to this source
        $managementHierarchies = $this->sourceManagementHierarchy->managementHierarchies()->with('children')->get();

        foreach ($managementHierarchies as $hierarchy) {
            // Get departments from this hierarchy tree
            $departments = $this->collectDepartmentsFromTree($hierarchy);
            $allDepartments = array_merge($allDepartments, $departments);
        }

        return $allDepartments;
    }

    /**
     * Recursively collect all departments from a management hierarchy tree
     */
    private function collectDepartmentsFromTree($hierarchy): array
    {
        $departments = [];

        // If this node is a department, add it to the collection
        if ($hierarchy->type === 'department') {
            $departments[] = [
                'id' => $hierarchy->id,
                'name' => $hierarchy->name,
                'users_count' => $hierarchy->users_count ?? 0,
                'is_active' => $hierarchy->is_active,
            ];
        }

        // Recursively process children
        if ($hierarchy->children && $hierarchy->children->count() > 0) {
            foreach ($hierarchy->children as $child) {
                $childDepartments = $this->collectDepartmentsFromTree($child);
                $departments = array_merge($departments, $childDepartments);
            }
        }

        return $departments;
    }


}
