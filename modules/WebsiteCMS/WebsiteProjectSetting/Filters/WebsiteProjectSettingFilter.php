<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteProjectSetting\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class WebsiteProjectSettingFilter extends SearchModelFilter
{
    public $relations = [];

    public function name($name)
    {
        return $this->whereHas("translations",function ($query) use ($name) {
            $query->where('content', 'like', '%' . $name . '%')
                  ->orWhere('content', 'like', '%' . $name . '%');
        });
    }


    public function search($name)
    {
        return $this->whereHas("translations",function ($query) use ($name) {
            $query->where('content', 'like', '%' . $name . '%')
                  ->orWhere('content', 'like', '%' . $name . '%');
        });
    }
}
