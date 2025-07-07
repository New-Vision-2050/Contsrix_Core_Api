<?php

declare(strict_types=1);

namespace Modules\Attendance\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ManagementHierarchyController extends Controller
{
    /**
     * Get all branches for the current company.
     */
    public function getBranches(): JsonResponse
    {
        $companyId = Auth::user()->company_id;
        
        $branches = ManagementHierarchy::where('company_id', $companyId)
            ->where('type', 'branch')
            ->with(['manager', 'parent'])
            ->orderBy('name')
            ->get();

        return Json::items($branches, message: 'Branches retrieved successfully');
    }

    /**
     * Get details of a specific branch.
     */
    public function getBranchDetails(string $branchId): JsonResponse
    {
        $companyId = Auth::user()->company_id;
        
        $branch = ManagementHierarchy::where('company_id', $companyId)
            ->where('type', 'branch')
            ->where('id', $branchId)
            ->with(['manager', 'parent', 'company'])
            ->firstOrFail();

        return Json::item($branch, message: 'Branch details retrieved successfully');
    }

    /**
     * Get child branches of a specific branch.
     */
    public function getBranchChildren(string $branchId): JsonResponse
    {
        $companyId = Auth::user()->company_id;
        
        $children = ManagementHierarchy::where('company_id', $companyId)
            ->where('type', 'branch')
            ->where('parent_id', $branchId)
            ->with(['manager'])
            ->orderBy('name')
            ->get();

        return Json::items($children, message: 'Branch children retrieved successfully');
    }

    /**
     * Get parent branches of a specific branch (hierarchy path).
     */
    public function getBranchParents(string $branchId): JsonResponse
    {
        $companyId = Auth::user()->company_id;
        
        $branch = ManagementHierarchy::where('company_id', $companyId)
            ->where('type', 'branch')
            ->where('id', $branchId)
            ->firstOrFail();
        
        $parents = [];
        $currentBranch = $branch;
        
        // Traverse up the hierarchy
        while ($currentBranch->parent_id) {
            $parent = ManagementHierarchy::where('company_id', $companyId)
                ->where('type', 'branch')
                ->where('id', $currentBranch->parent_id)
                ->with(['manager'])
                ->first();
            
            if ($parent) {
                $parents[] = $parent;
                $currentBranch = $parent;
            } else {
                break;
            }
        }

        return Json::items(collect($parents), message: 'Branch parents retrieved successfully');
    }

    /**
     * Get the branch that a user belongs to.
     */
    public function getUserBranch(string $userId): JsonResponse
    {
        $companyId = Auth::user()->company_id;
        
        $user = User::where('company_id', $companyId)
            ->where('id', $userId)
            ->with(['managementHierarchy' => function ($query) {
                $query->where('type', 'branch')->with(['manager', 'parent']);
            }])
            ->firstOrFail();
        
        $branch = $user->managementHierarchy;
        
        if (!$branch) {
            return Json::item(null, message: 'User is not assigned to any branch');
        }

        return Json::item($branch, message: 'User branch retrieved successfully');
    }

    /**
     * Get all users belonging to a specific branch.
     */
    public function getBranchUsers(string $branchId): JsonResponse
    {
        $companyId = Auth::user()->company_id;
        
        // Verify branch exists and belongs to company
        $branch = ManagementHierarchy::where('company_id', $companyId)
            ->where('type', 'branch')
            ->where('id', $branchId)
            ->firstOrFail();
        
        $users = User::where('company_id', $companyId)
            ->whereHas('managementHierarchy', function ($query) use ($branchId) {
                $query->where('id', $branchId);
            })
            ->with(['roles', 'permissions'])
            ->orderBy('name')
            ->get();

        return Json::items($users, message: 'Branch users retrieved successfully');
    }
}
