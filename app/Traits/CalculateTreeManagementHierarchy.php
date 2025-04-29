<?php

declare(strict_types=1);

namespace App\Traits;

use App\Scopes\CustomTenantScope;
use Modules\Company\CompanyCore\Models\Company;
use Stancl\Tenancy\Contracts\Tenant;

/**
 * @property-read Tenant $tenant
 */
trait CalculateTreeManagementHierarchy
{
    function calculateHierarchyCounts($children)
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

}
