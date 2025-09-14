<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoCategory\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class EcoCategoryFilter extends SearchModelFilter
{
    public $relations = ['parent', 'children', 'translations', 'products'];

    /**
     * Filter by category name (search in translations)
     */
    public function name($name)
    {
        return $this->whereHas('translations', function ($q) use ($name) {
            $q->where('content', 'like', '%' . $name . '%');
        });
    }

    /**
     * Filter by category description (search in translations)
     */
    public function description($description)
    {
        return $this->whereHas('translations', function ($q) use ($description) {
            $q->where('content', 'like', '%' . $description . '%');
        });
    }

    /**
     * General search filter (searches in name, description, code)
     */
    public function search($search)
    {
        return $this->where(function ($query) use ($search) {
                  $query->whereHas('translations', function ($q) use ($search) {
                      $q->where('content', 'like', '%' . $search . '%');
                  });   
        });
    }

    /**
     * Filter by parent category ID
     */
    public function parent($parentId)
    {
        if ($parentId === 'null' || $parentId === null) {
            return $this->whereNull('parent_id');
        }
        return $this->where('parent_id', $parentId);
    }

    /**
     * Filter by multiple parent category IDs
     */
    public function parents($parentIds)
    {
        if (is_array($parentIds)) {
            return $this->whereIn('parent_id', $parentIds);
        }
        return $this->where('parent_id', $parentIds);
    }

    /**
     * Filter by category status (active/inactive)
     */
    public function status($status)
    {
        if ($status === 'active') {
            return $this->where('is_active', true);
        } elseif ($status === 'inactive') {
            return $this->where('is_active', false);
        }
        return $this->where('is_active', (bool) $status);
    }

    /**
     * Filter by category level (main/sub categories)
     */
    public function level($level)
    {
        switch ($level) {
            case 'main':
            case 'parent':
            case 'root':
                return $this->whereNull('parent_id');
            case 'sub':
            case 'child':
            case 'subcategory':
                return $this->whereNotNull('parent_id');
            default:
                return $this;
        }
    }

    /**
     * Filter categories that have products
     */
    public function hasProducts($hasProducts = true)
    {
        if ($hasProducts) {
            return $this->whereHas('products');
        } else {
            return $this->whereDoesntHave('products');
        }
    }

    /**
     * Filter by creation date range
     */
    public function createdFrom($date)
    {
        return $this->where('created_at', '>=', $date);
    }

    public function createdTo($date)
    {
        return $this->where('created_at', '<=', $date);
    }

    /**
     * Filter by sort order range
     */
    public function sortOrderFrom($order)
    {
        return $this->where('sort_order', '>=', $order);
    }

    public function sortOrderTo($order)
    {
        return $this->where('sort_order', '<=', $order);
    }

    /**
     * Filter by category depth/level in hierarchy
     */
    public function depth($depth)
    {
        switch ($depth) {
            case 0:
            case '0':
                return $this->whereNull('parent_id');
            case 1:
            case '1':
                return $this->whereNotNull('parent_id')
                           ->whereHas('parent', function ($q) {
                               $q->whereNull('parent_id');
                           });
            default:
                return $this;
        }
    }

    /**
     * Sort by category name
     */
    public function sortByName($direction = 'asc')
    {
        return $this->select('eco_categories.*')
            ->leftJoin('translations', function ($join) {
                $join->on('eco_categories.id', '=', 'translations.translatable_id')
                     ->where('translations.translatable_type', 'Modules\\Ecommerce\\EcoCategory\\Models\\EcoCategory')
                     ->where('translations.locale', app()->getLocale() ?? 'en');
            })->orderBy('translations.content', $direction);
    }

    /**
     * Sort by creation date
     */
    public function sortByNewest()
    {
        return $this->orderBy('created_at', 'desc');
    }

    /**
     * Sort by product count
     */
    public function sortByProductCount($direction = 'desc')
    {
        return $this->withCount('products')->orderBy('products_count', $direction);
    }
}

