<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\JobTitle\Models\JobTitle;
use Modules\JobTitle\Presenters\JobTitlePresenter;
use Modules\Shared\JobType\Models\JobType;
use Modules\Shared\JobType\Presenters\JobTypePresenter;

class ManagementHierarchyLookupsPresenter extends AbstractPresenter
{
    public function __construct(
        private array $lookups
    ) {
    }



    private function getJobTypes(): array
    {
        return JobTypePresenter::collection($this->lookups['job_types']);
    }

    private function getJobTitles(): array
    {
        return JobTitlePresenter::collection($this->lookups['job_titles']);
    }

    protected function present(bool $isListing = false): ?array
    {
        return [
            'job_types' => $this->getJobTypes(),
            'job_titles' => $this->getJobTitles(),
        ];    }
}
