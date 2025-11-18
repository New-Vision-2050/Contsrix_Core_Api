<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\Founder\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class FounderFilter extends SearchModelFilter
{
    public $relations = [];

    public function name($name)
    {
        return $this->where(function ($query) use ($name) {
            $query->where('name->ar', 'like', '%' . $name . '%')
                  ->orWhere('name->en', 'like', '%' . $name . '%');
        });
    }

    public function job_title($jobTitle)
    {
        return $this->where(function ($query) use ($jobTitle) {
            $query->where('job_title->ar', 'like', '%' . $jobTitle . '%')
                  ->orWhere('job_title->en', 'like', '%' . $jobTitle . '%');
        });
    }

    public function status($status)
    {
        return $this->where('status', $status);
    }
}
