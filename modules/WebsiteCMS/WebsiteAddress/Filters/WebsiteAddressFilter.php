<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteAddress\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class WebsiteAddressFilter extends SearchModelFilter
{
    public $relations = ['city'];

    public function title($title)
    {
        return $this->whereHas("translations", function ($query) use ($title) {
            $query->where(function ($q) use ($title) {
                $q->where('content', 'like', '%' . $title . '%')
                    ->orWhere('content', 'like', '%' . $title . '%');
            })->where("field", "title");
        });
    }


    public function search($title)
    {
        return $this->whereHas("translations", function ($query) use ($title) {
            $query->where(function ($q) use ($title) {
                $q->where('content', 'like', '%' . $title . '%')
                    ->orWhere('content', 'like', '%' . $title . '%');
            })->where("field", "title");
        });
    }

    public function city($cityId)
    {
        return $this->where('city_id', $cityId);
    }

    public function status($status)
    {
        return $this->where('status', $status);
    }
}
