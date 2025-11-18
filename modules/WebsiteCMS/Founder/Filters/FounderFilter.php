<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\Founder\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class FounderFilter extends SearchModelFilter
{
    public $relations = [];

    public function name($name)
    {
        return $this->whereHas("translations", function ($query) use ($name) {
            $query->where(function ($q) use ($name) {
                $q->where('content', 'like', '%' . $name . '%')
                    ->orWhere('content', 'like', '%' . $name . '%');
            })->where("field", "name");
        });
    }

    public function job_title($jobTitle)
    {
        return $this->whereHas("translations", function ($query) use ($jobTitle) {
            $query->where(function ($q) use ($jobTitle) {
                $q->where('content', 'like', '%' . $jobTitle . '%')
                    ->orWhere('content', 'like', '%' . $jobTitle . '%');
            })->where("field", "job_title");
        });
    }

    public function search($search)
    {
        return $this->whereHas("translations", function ($query) use ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('content', 'like', '%' . $search . '%')
                    ->orWhere('content', 'like', '%' . $search . '%');
            });
        });
    }

    public function status($status)
    {
        return $this->where('status', $status);
    }
}
