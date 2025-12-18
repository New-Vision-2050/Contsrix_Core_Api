<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteProject\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class WebsiteProjectFilter extends SearchModelFilter
{
    public $relations = [];

    public function name($name)
    {
        return $this->whereHas("translations",function ($q)use ($name){
            $q->where("content", "like", "%{$name}%");
        });
    }

    public function status($status)
    {
        return $this->where("status", $status);
    }

    public function websiteProjectSetting($id)
    {
        return $this->where("website_project_setting_id", $id);
    }
}
