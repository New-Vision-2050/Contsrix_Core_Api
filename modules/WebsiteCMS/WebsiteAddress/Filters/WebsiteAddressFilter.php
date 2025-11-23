<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteAddress\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class WebsiteAddressFilter extends SearchModelFilter
{
    public $relations = ['city'];

    public function title($title)
    {
        return $this->where(function ($query) use ($title) {
            $query->where('title->ar', 'like', '%' . $title . '%')
                  ->orWhere('title->en', 'like', '%' . $title . '%');
        });
    }

    public function cityId($cityId)
    {
        return $this->where('city_id', $cityId);
    }

    public function status($status)
    {
        return $this->where('status', $status);
    }
}
