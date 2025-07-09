<?php

namespace Modules\Company\ManagementHierarchy\Presenters;

use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;

class NonCopiedHierarchyWithDetailsPresenter
{
    public function present(ManagementHierarchy $managementHierarchy): array
    {
        return [
            'id' => $managementHierarchy->id,
            'name' => $managementHierarchy->name,
            'parent_id' => $managementHierarchy->parent_id,
            'type' => $managementHierarchy->type,
            'is_active' => $managementHierarchy->is_active,
            'users_count' => $managementHierarchy->users_count,
            'manager' => $managementHierarchy->user ? [
                'id' => $managementHierarchy->user->id,
                'name' => $managementHierarchy->user->name,
                'email' => $managementHierarchy->user->email,
            ] : null,
            'detail' => $managementHierarchy->detail ? [
                'is_copied' => (bool) $managementHierarchy->detail->is_copied,
            ] : null,
            'job_titles' => $managementHierarchy->jobTitles->map(function ($jobTitle) {
                return [
                    'id' => $jobTitle->id,
                    'name' => $jobTitle->name,
                ];
            }),
            'job_types' => $managementHierarchy->jobTypes->map(function ($jobType) {
                return [
                    'id' => $jobType->id,
                    'name' => $jobType->name,
                ];
            }),
            'related_branches' => $managementHierarchy->relatedBranches->map(function ($branch) {
                return [
                    'id' => $branch->id,
                    'name' => $branch->name,
                ];
            }),
            'deputy_managers' => $managementHierarchy->deputyManagers->map(function ($deputy) {
                return [
                    'id' => $deputy->user->id,
                    'name' => $deputy->user->name,
                    'email' => $deputy->user->email,
                ];
            }),
            'created_at' => $managementHierarchy->created_at->toIso8601String(),
            'updated_at' => $managementHierarchy->updated_at->toIso8601String(),
        ];
    }
}
