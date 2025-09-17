<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Company\ManagementHierarchy\Repositories\ManagementHierarchyRepository;
use Modules\Shared\JobType\Repositories\JobTypeRepository;
use Modules\JobTitle\Repositories\JobTitleRepository;

class ManagementHierarchyLookupsService
{
    public function __construct(
        private JobTypeRepository $jobTypeRepository,
        private JobTitleRepository $jobTitleRepository,
    ) {
    }

    /**
     * Get all lookups
     */
    public function getAllLookups(?array $jobTypeIds = null): array
    {
        return [
            'job_types' => $this->getJobTypes(),
            'job_titles' => $this->getJobTitles($jobTypeIds),
        ];
    }



    /**
     * Get job types lookup
     */
    public function getJobTypes(): Collection
    {
        return $this->jobTypeRepository->getAllJobTypes();
    }

    /**
     * Get job titles lookup filtered by job type IDs
     */
    public function getJobTitles(?array $jobTypeIds = null): Collection
    {
        if ($jobTypeIds && !empty($jobTypeIds)) {
            return $this->jobTitleRepository->getJobTitlesByJobTypeIds($jobTypeIds);
        }
        
        return $this->jobTitleRepository->getAllJobTitles();
    }
}
