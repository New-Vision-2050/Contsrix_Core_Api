<?php

declare(strict_types=1);

namespace Modules\Shared\JobType\Presenters;

use Modules\JobTitle\Presenters\JobTitleInTypePresenter;
use Modules\JobTitle\Presenters\JobTitlePresenter;
use Modules\Shared\JobType\Models\JobType;
use BasePackage\Shared\Presenters\AbstractPresenter;

class JobTypePresenter extends AbstractPresenter
{
    private JobType $jobType;

    public function __construct(JobType $jobType)
    {
        $this->jobType = $jobType;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->jobType->id,
            'name' => $this->jobType->name,
            'status' => $this->jobType->status,
            "job_titles"=>JobTitleInTypePresenter::collection($this->jobType->jobTitles),
//            'job_titles' => $this->jobType->jobTitles,
            "user_count"=>$this->jobType->userProfissional()->count(),
        ];
    }
}
