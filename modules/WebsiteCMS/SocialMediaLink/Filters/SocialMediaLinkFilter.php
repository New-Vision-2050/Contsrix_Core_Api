<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\SocialMediaLink\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class SocialMediaLinkFilter extends SearchModelFilter
{
    public $relations = [];

    public function type($type)
    {
        return $this->where('type', $type);
    }

    public function status($status)
    {
        return $this->where('status', $status);
    }

    public function link($link)
    {
        return $this->where('link', 'like', '%' . $link . '%');
    }
    public function search($link)
    {
        return $this->where('link', 'like', '%' . $link . '%');
    }


}
