<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class FeatureFilter extends SearchModelFilter
{
    public $relations = [];

    /**
     * Filter by feature type
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
     * General search filter (searches in title and description)
     */
    public function search($search)
    {
        return $this->where(function ($query) use ($search) {
            $query->where('title', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
        });
    }

    /**
     * Filter by title
     */
    public function title($title)
    {
        return $this->where('title', 'like', '%' . $title . '%');
    }

    /**
     * Filter by description
     */
    public function description($description)
    {
        return $this->where('description', 'like', '%' . $description . '%');
    }

    /**
     * Filter by active features only
     */
    public function activeOnly($activeOnly)
    {
        if ($activeOnly) {
            return $this->where('is_active', 1);
        }
        return $this;
    }

    /**
     * Filter by inactive features only
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
