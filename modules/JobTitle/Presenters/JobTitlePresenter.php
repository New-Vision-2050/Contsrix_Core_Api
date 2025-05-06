<?php

declare(strict_types=1);

namespace Modules\JobTitle\Presenters;

use Modules\JobTitle\Models\JobTitle;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Shared\JobType\Models\JobType;
use Modules\Shared\JobType\Presenters\JobTypePresenter;

class JobTitlePresenter extends AbstractPresenter
{
    private JobTitle $jobTitle;

    public function __construct(JobTitle $jobTitle)
    {
        $this->jobTitle = $jobTitle;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->jobTitle->id,
            'name' => $this->jobTitle->name,
            'type' => $this->jobTitle->type,
            'description' => $this->jobTitle->description,
            "status"=>$this->jobTitle->status,
            "job_type"=>$this->jobTitle->jobType?(new JobTypePresenter($this->jobTitle->jobType))->getData():null,
        ];
    }
}
