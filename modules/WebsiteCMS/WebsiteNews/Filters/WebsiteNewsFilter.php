<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteNews\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class WebsiteNewsFilter extends SearchModelFilter
{
    public $relations = ['category'];

    public function title($title)
    {
        return $this->whereHas("translations", function ($query) use ($title) {
            $query->where(function ($q) use ($title) {
                $q->where('content', 'like', '%' . $title . '%')
                    ->orWhere('content', 'like', '%' . $title . '%');
            })->where("field", "title");
        });
    }

    public function content($content)
    {
        return $this->whereHas("translations", function ($query) use ($content) {
            $query->where(function ($q) use ($content) {
                $q->where('content', 'like', '%' . $content . '%')
                    ->orWhere('content', 'like', '%' . $content . '%');
            })->where("field", "content");
        });
    }


    public function search($content)
    {
        return $this->whereHas("translations", function ($query) use ($content) {
            $query->where(function ($q) use ($content) {
                $q->where('content', 'like', '%' . $content . '%')
                    ->orWhere('content', 'like', '%' . $content . '%');
            });
        });
    }


    public function categoryWebsiteCms($categoryId)
    {
        return $this->where('category_website_cms_id', $categoryId);
    }

    public function publish_date($publishDate)
    {
        return $this->where('publish_date', $publishDate);
    }

    public function status($status)
    {
        return $this->where('status', $status);
    }
}
