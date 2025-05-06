<?php

declare(strict_types=1);

namespace Modules\Shared\JobType\Presenters;

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
        ];
    }
}
