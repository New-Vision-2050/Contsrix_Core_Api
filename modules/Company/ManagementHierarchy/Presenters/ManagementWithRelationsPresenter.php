<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\Company\ManagementHierarchy\Models\SourceManagementHierarchy;
use Modules\Shared\JobType\Models\JobType;
use Modules\Shared\JobType\Presenters\JobTypePresenter;
use Modules\JobTitle\Presenters\JobTitlePresenter;
use Modules\User\Presenters\UserPresenter;

class ManagementWithRelationsPresenter extends AbstractPresenter
{
    public function __construct(
        private SourceManagementHierarchy $sourceManagementHierarchy
    ) {
    }





    private function getJobTypes(): array
    {
        return JobTypePresenter::collection($this->sourceManagementHierarchy->jobTypes);
    }

    private function getJobTitles(): array
    {
        return JobTitlePresenter::collection($this->sourceManagementHierarchy->jobTitles);
    }

    private function getRelatedBranches(): array
    {
        return ManagementHierarchySimpleDataPresenter::collection( $this->sourceManagementHierarchy->relatedBranches);

    }


    private function getRelatedManagements(): array
    {
        return ManagementHierarchySimpleDataPresenter::collection( $this->sourceManagementHierarchy->relatedManagements);

    }


    protected function present(bool $isListing = false): ?array
    {
        return [
            'id' => $this->sourceManagementHierarchy->id,
            'name' => $this->sourceManagementHierarchy->name,
            'company_id' => $this->sourceManagementHierarchy->company_id,
            'is_active' => $this->sourceManagementHierarchy->is_active,
            'type' => $this->sourceManagementHierarchy->type,
            'users_count' => $this->sourceManagementHierarchy->managementHierarchies->sum(function ($clone) {
                return $clone->users_count ?? 0;
            }),
            "departments_count"=>$this->sourceManagementHierarchy->managementHierarchies->sum(function ($clone) {
                return $clone->cacheHierarchyCounts()["department_count"]??0 ;
            }),

            'job_types' => $this->getJobTypes(),
            'job_titles' => $this->getJobTitles(),
            'related_branches' => $this->getRelatedBranches(),
            'related_managements' => $this->getRelatedBranches(),
            'created_at' => $this->sourceManagementHierarchy->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->sourceManagementHierarchy->updated_at?->format('Y-m-d H:i:s'),
        ];

    }
}
