<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\CategoryWebsiteCMS\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class CategoryWebsiteCMSFilter extends SearchModelFilter
{
    public $relations = [];

    public function name($name)
    {
        return $this->whereHas("translations",function ($query) use ($name) {
            $query->where('content', 'like', '%' . $name . '%');
        });
    }

    public function CategoryType($type)
    {
        return $this->where('category_type', $type);
    }
}
