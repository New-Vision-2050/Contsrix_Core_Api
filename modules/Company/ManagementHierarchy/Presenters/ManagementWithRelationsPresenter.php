<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Presenters;

use BasePackage\Shared\Presenters\BasePresenter;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\Shared\JobType\Presenters\JobTypePresenter;
use Modules\JobTitle\Presenters\JobTitlePresenter;
use Modules\User\Presenters\UserPresenter;

class ManagementWithRelationsPresenter extends BasePresenter
{
    public function __construct(
        private ManagementHierarchy $managementHierarchy
    ) {
    }

    public function getData(): array
    {
        return [
            'id' => $this->managementHierarchy->id,
            'name' => $this->managementHierarchy->name,
            'parent_id' => $this->managementHierarchy->parent_id,
            'company_id' => $this->managementHierarchy->company_id,
            'is_main' => $this->managementHierarchy->is_main,
            'is_active' => $this->managementHierarchy->is_active,
            'type' => $this->managementHierarchy->type,
            'users_count' => $this->managementHierarchy->users_count,
            'manager' => $this->managementHierarchy->user ? 
                (new UserPresenter($this->managementHierarchy->user))->getData() : null,
            'detail' => $this->getDetail(),
            'job_types' => $this->getJobTypes(),
            'job_titles' => $this->getJobTitles(),
            'related_branches' => $this->getRelatedBranches(),
            'deputy_managers' => $this->getDeputyManagers(),
            'created_at' => $this->managementHierarchy->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->managementHierarchy->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    private function getDetail(): ?array
    {
        if (!$this->managementHierarchy->detail) {
            return null;
        }

        return [
            'id' => $this->managementHierarchy->detail->id,
            'description' => $this->managementHierarchy->detail->description,
            'branch_id' => $this->managementHierarchy->detail->branch_id,
            'reference_department_id' => $this->managementHierarchy->detail->reference_department_id,
            'is_copied' => $this->managementHierarchy->detail->is_copied,
            'reference_user_id' => $this->managementHierarchy->detail->reference_user_id,
        ];
    }

    private function getJobTypes(): array
    {
        return $this->managementHierarchy->jobTypes->map(function ($jobType) {
            return [
                'id' => $jobType->id,
                'name' => $jobType->name,
                'status' => $jobType->status,
                'pivot' => [
                    'created_at' => $jobType->pivot->created_at?->format('Y-m-d H:i:s'),
                    'updated_at' => $jobType->pivot->updated_at?->format('Y-m-d H:i:s'),
                ]
            ];
        })->toArray();
    }

    private function getJobTitles(): array
    {
        return $this->managementHierarchy->jobTitles->map(function ($jobTitle) {
            return [
                'id' => $jobTitle->id,
                'name' => $jobTitle->name,
                'type' => $jobTitle->type,
                'description' => $jobTitle->description,
                'status' => $jobTitle->status,
                'pivot' => [
                    'created_at' => $jobTitle->pivot->created_at?->format('Y-m-d H:i:s'),
                    'updated_at' => $jobTitle->pivot->updated_at?->format('Y-m-d H:i:s'),
                ]
            ];
        })->toArray();
    }

    private function getRelatedBranches(): array
    {
        return $this->managementHierarchy->relatedBranches->map(function ($branch) {
            return [
                'id' => $branch->id,
                'name' => $branch->name,
                'type' => $branch->type,
                'is_active' => $branch->is_active,
                'users_count' => $branch->users_count,
                'pivot' => [
                    'created_at' => $branch->pivot->created_at?->format('Y-m-d H:i:s'),
                    'updated_at' => $branch->pivot->updated_at?->format('Y-m-d H:i:s'),
                ]
            ];
        })->toArray();
    }

    private function getDeputyManagers(): array
    {
        if (!$this->managementHierarchy->detail || !$this->managementHierarchy->deputyManagers) {
            return [];
        }

        return $this->managementHierarchy->deputyManagers->map(function ($user) {
            return (new UserPresenter($user))->getData();
        })->toArray();
    }
}
