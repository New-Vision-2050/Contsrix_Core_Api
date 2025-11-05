<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class BannerFilter extends SearchModelFilter
{
    public $relations = [];

    /**
     * Filter by banner type
     */
    public function type($type)
    {
        return $this->where('type', $type);
    }

    /**
     * Filter by active status
     */
    public function isActive($isActive)
    {
        return $this->where('is_active', $isActive);
    }

    /**
     * General search filter (searches in URL, title, and description)
     */
    public function search($search)
    {
        return $this->where(function ($query) use ($search) {
            $query->where('url', 'like', '%' . $search . '%')
                  ->orWhere('title', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
        });
    }

    /**
     * Filter by URL
     */
    public function url($url)
    {
        return $this->where('url', 'like', '%' . $url . '%');
    }

    /**
     * Filter by active banners only
     */
    public function activeOnly($activeOnly)
    {
        if ($activeOnly) {
            return $this->where('is_active', 1);
        }
        return $this;
    }

    /**
     * Filter by inactive banners only
     */
    public function inactiveOnly($inactiveOnly)
    {
        if ($inactiveOnly) {
            return $this->where('is_active', 0);
        }
        return $this;
    }

    /**
     * Filter by creation date from
     */
    public function createdFrom($createdFrom)
    {
        return $this->where('created_at', '>=', $createdFrom);
    }

    /**
     * Filter by creation date to
     */
    public function createdTo($createdTo)
    {
        return $this->where('created_at', '<=', $createdTo . ' 23:59:59');
    }

    /**
     * Filter by update date from
     */
    public function updatedFrom($updatedFrom)
    {
        return $this->where('updated_at', '>=', $updatedFrom);
    }

    /**
     * Filter by update date to
     */
    public function updatedTo($updatedTo)
    {
        return $this->where('updated_at', '<=', $updatedTo . ' 23:59:59');
    }
}
