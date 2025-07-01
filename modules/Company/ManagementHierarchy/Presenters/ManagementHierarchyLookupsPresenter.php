<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Presenters;

use BasePackage\Shared\Presenters\BasePresenter;

class ManagementHierarchyLookupsPresenter extends BasePresenter
{
    public function __construct(
        private array $lookups
    ) {
    }

    public function getData(): array
    {
        return [
            'job_types' => $this->getJobTypes(),
            'job_titles' => $this->getJobTitles(),
        ];
    }

    private function getJobTypes(): array
    {
        return $this->lookups['job_types']->map(function ($jobType) {
            return [
                'id' => $jobType->id,
                'name' => $jobType->name,
                'status' => $jobType->status,
            ];
        })->toArray();
    }

    private function getJobTitles(): array
    {
        return $this->lookups['job_titles']->map(function ($jobTitle) {
            return [
                'id' => $jobTitle->id,
                'name' => $jobTitle->name,
                'type' => $jobTitle->type,
                'description' => $jobTitle->description,
                'status' => $jobTitle->status,
                'job_type_id' => $jobTitle->job_type_id,
            ];
        })->toArray();
    }
}
