<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteService\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class WebsiteServiceFilter extends SearchModelFilter
{
    public $relations = [];

    public function name($name)
    {
        return $this->whereHas("translations",function ($q) use ($name) {
            $q->where('content', 'like', '%' . $name . '%')
                ->orWhere('content', 'like', '%' . $name . '%');
        });
    }


    public function search($name)
    {
        return $this->whereHas("translations",function ($q) use ($name) {
            $q->where('content', 'like', '%' . $name . '%')
                ->orWhere('content', 'like', '%' . $name . '%');
        });
    }

    public function referenceNumber($referenceNumber)
    {
        return $this->where('reference_number', $referenceNumber);
    }

    public function categoryWebsiteCMS($categoryWebsiteCMSId)
    {
        return $this->where('category_website_cms_id', $categoryWebsiteCMSId);
    }
}
