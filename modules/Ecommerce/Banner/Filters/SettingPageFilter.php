<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class SettingPageFilter extends SearchModelFilter
{
    public $relations = [];

    /**
     * Filter by setting page type
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
     * General search filter (searches in title and description fields)
     */
    public function search($search)
    {
        return $this->where(function ($query) use ($search) {
            $query->where('title_header', 'like', '%' . $search . '%')
                  ->orWhere('description_header', 'like', '%' . $search . '%')
                  ->orWhere('title_footer', 'like', '%' . $search . '%')
                  ->orWhere('description_footer', 'like', '%' . $search . '%');
        });
    }

    /**
     * Filter by title header
     */
    public function titleHeader($titleHeader)
    {
        return $this->where('title_header', 'like', '%' . $titleHeader . '%');
    }

    /**
     * Filter by title footer
     */
    public function titleFooter($titleFooter)
    {
        return $this->where('title_footer', 'like', '%' . $titleFooter . '%');
    }

    /**
     * Filter by active setting pages only
     */
    public function activeOnly($activeOnly)
    {
        if ($activeOnly) {
            return $this->where('is_active', true);
        }
        return $this;
    }

    /**
     * Filter by inactive setting pages only
     */
    public function inactiveOnly($inactiveOnly)
    {
        if ($inactiveOnly) {
            return $this->where('is_active', false);
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
