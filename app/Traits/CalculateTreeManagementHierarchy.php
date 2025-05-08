<?php

declare(strict_types=1);

namespace App\Traits;

use App\Scopes\CustomTenantScope;
use Modules\Company\CompanyCore\Models\Company;
use Stancl\Tenancy\Contracts\Tenant;
use Illuminate\Support\Facades\Cache;

/**
 * @property-read Tenant $tenant
 */
trait CalculateTreeManagementHierarchy
{
    /**
     * Calculate hierarchy counts recursively
     *
     * @param array $children
     * @return array [branch_count, management_count, department_count]
     */
    public function calculateHierarchyCounts($children)
    {
        $branch = 0;
        $management = 0;
        $department = 0;

        foreach ($children as $child) {
            // Count the current node based on its type
            if ($child->type == "department") $department++;
            if ($child->type == "management") $management++;
            if ($child->type == "branch") $branch++;

            // If the node has children, get their counts and add them to our totals
            if (!empty($child->children)) {
                $childCounts = $this->calculateHierarchyCounts($child->children);
                $branch += $childCounts[0];
                $management += $childCounts[1];
                $department += $childCounts[2];
            }
        }

        return [$branch, $management, $department];
    }

    /**
     * Calculate and cache hierarchy counts for a management hierarchy node
     *
     * @return array [branch_count, management_count, department_count]
     */
    public function cacheHierarchyCounts()
    {
        // Calculate descendants and their counts only if they're not already cached
        $cacheKey = $this->getHierarchyCountsCacheKey();

        return Cache::remember($cacheKey, now()->addDay(), function () {
            $descendants = $this->descendants()->with('descendants')->get();

            // Excluding the current node from counts if it's of the same type
            $branchCount = $descendants->where('type', 'branch')->count();
            $managementCount = $descendants->where('type', 'management')->count();
            $departmentCount = $descendants->where('type', 'department')->count();

//            // Adjust counts if the current node is of the same type (to avoid counting itself)
//            if ($this->type == 'branch') $branchCount--;
//            if ($this->type == 'management') $managementCount--;
//            if ($this->type == 'department') $departmentCount--;
//
            // Ensure no negative counts
            $branchCount = max(0, $branchCount);
            $managementCount = max(0, $managementCount);
            $departmentCount = max(0, $departmentCount);

            return [
                'branch_count' => $branchCount,
                'management_count' => $managementCount,
                'department_count' => $departmentCount
            ];
        });
    }

    /**
     * Get cached hierarchy counts
     *
     * @return array|null Cached counts or null if not cached
     */
    public function getCachedHierarchyCounts()
    {
        $cacheKey = $this->getHierarchyCountsCacheKey();
        return Cache::get($cacheKey);
    }

    /**
     * Refresh cached hierarchy counts
     *
     * @return array Updated counts
     */
    public function refreshHierarchyCounts()
    {
        $cacheKey = $this->getHierarchyCountsCacheKey();
        Cache::forget($cacheKey);
        return $this->cacheHierarchyCounts();
    }

    /**
     * Generate a cache key for hierarchy counts
     *
     * @return string
     */
    protected function getHierarchyCountsCacheKey()
    {
        return "hierarchy_counts:{$this->id}:" . ($this->company_id ?? 'global');
    }
}
