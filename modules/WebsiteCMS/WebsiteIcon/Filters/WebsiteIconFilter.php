<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteIcon\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class WebsiteIconFilter extends SearchModelFilter
{
    public $relations = [];

    public function name($name)
    {
        return $this->where(function ($query) use ($name) {
            $query->where('name->ar', 'like', '%' . $name . '%')
                  ->orWhere('name->en', 'like', '%' . $name . '%');
        });
    }

    public function websiteIconCategoryType($categoryType)
    {
        return $this->where('website_icon_category_type', $categoryType);
    }
}
