<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteOurService\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class WebsiteOurServiceFilter extends SearchModelFilter
{
    public $relations = [];

    public function title($title)
    {
        return $this->where(function ($query) use ($title) {
            $query->where('title->ar', 'like', '%' . $title . '%')
                  ->orWhere('title->en', 'like', '%' . $title . '%');
        });
    }


    public function search($title)
    {
        return $this->where(function ($query) use ($title) {
            $query->where('title->ar', 'like', '%' . $title . '%')
                  ->orWhere('title->en', 'like', '%' . $title . '%');
        });
    }

    public function status($status)
    {
        return $this->where('status', $status);
    }

    public function type($type)
    {
        return $this->whereHas('departments', function ($query) use ($type) {
            $query->where('type', $type);
        });
    }
}
