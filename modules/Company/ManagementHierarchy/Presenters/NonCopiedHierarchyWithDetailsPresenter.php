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
                'is_copied' => (bool)$managementHierarchy->detail->is_copied,
            ] : null,
            'job_titles' => $managementHierarchy->jobTitles->map(fn ($jobTitle) => [
                'id' => $jobTitle->id,
                'name' => $jobTitle->name,
            ]),
            'job_types' => $managementHierarchy->jobTypes->map(fn ($jobType) => [
                'id' => $jobType->id,
                'name' => $jobType->name,
            ]),
            'related_branches' => $managementHierarchy->relatedBranches->map(fn ($branch) => [
                'id' => $branch->id,
                'name' => $branch->name,
            ]),
            'deputy_managers' => $managementHierarchy->deputyManagers->map(fn ($deputy) => [
                'id' => $deputy->user->id,
                'name' => $deputy->user->name,
                'email' => $deputy->user->email,
            ]),
            'created_at' => $managementHierarchy->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $managementHierarchy->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
