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
     * Enhanced search filter with multiple fields
     */
    public function searchEnhanced($search)
    {
        return $this->where(function ($query) use ($search) {
            // Search in translations (name, description)
            $query->whereHas('translations', function ($q) use ($search) {
                $q->where('content', 'like', '%' . $search . '%');
            })
            // Search in priority field
            ->orWhere('priority', 'like', '%' . $search . '%')
            // Search in parent category name if it's a subcategory
            ->orWhereHas('parent.translations', function ($q) use ($search) {
                $q->where('content', 'like', '%' . $search . '%');
            });
        });
    }

    /**
     * Search by exact name match
     */
    public function exactName($name)
    {
        return $this->whereHas('translations', function ($q) use ($name) {
            $q->where('content', $name);
        });
    }

    /**
     * Search categories that start with specific text
     */
    public function startsWith($text)
    {
        return $this->whereHas('translations', function ($q) use ($text) {
            $q->where('content', 'like', $text . '%');
        });
    }

    /**
     * Search categories that end with specific text
     */
    public function endsWith($text)
    {
        return $this->whereHas('translations', function ($q) use ($text) {
            $q->where('content', 'like', '%' . $text);
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
     * Filter by is_active field directly
     */
    public function isActive($isActive)
    {
        return $this->where('is_active', (bool) $isActive);
    }

    /**
     * Filter only active categories
     */
    public function active()
    {
        return $this->where('is_active', true);
    }

    /**
     * Filter only inactive categories
     */
    public function inactive()
    {
        return $this->where('is_active', false);
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
            case 'category':
                return $this->whereNull('parent_id');
            case 'sub':
            case 'child':
            case 'subcategory':
            case 'sub_category':
                return $this->whereNotNull('parent_id')
                           ->whereHas('parent', function ($q) {
                               $q->whereNull('parent_id');
                           });
            case 'sub_sub_category':
            case 'subsub':
            case 'third_level':
                return $this->whereNotNull('parent_id')
                           ->whereHas('parent', function ($q) {
                               $q->whereNotNull('parent_id')
                                 ->whereHas('parent', function ($subQ) {
                                     $subQ->whereNull('parent_id');
                                 });
                           });
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
            case 2:
            case '2':
                return $this->whereNotNull('parent_id')
                           ->whereHas('parent', function ($q) {
                               $q->whereNotNull('parent_id')
                                 ->whereHas('parent', function ($subQ) {
                                     $subQ->whereNull('parent_id');
                                 });
                           });
            default:
                return $this;
        }
    }

    /**
     * Filter to get only main categories (level 0)
     */
    public function onlyMainCategories()
    {
        return $this->whereNull('parent_id');
    }

    /**
     * Filter to get only sub categories (level 1)
     */
    public function onlySubCategories()
    {
        return $this->whereNotNull('parent_id')
                   ->whereHas('parent', function ($q) {
                       $q->whereNull('parent_id');
                   });
    }

    /**
     * Filter to get only sub-sub categories (level 2)
     */
    public function onlySubSubCategories()
    {
        return $this->whereNotNull('parent_id')
                   ->whereHas('parent', function ($q) {
                       $q->whereNotNull('parent_id')
                         ->whereHas('parent', function ($subQ) {
                             $subQ->whereNull('parent_id');
                         });
                   });
    }

    /**
     * Filter by priority range
     */
    public function priorityFrom($priority)
    {
        return $this->where('priority', '>=', $priority);
    }

    public function priorityTo($priority)
    {
        return $this->where('priority', '<=', $priority);
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

    /**
     * Filter by multiple status values
     */
    public function statuses($statuses)
    {
        if (is_array($statuses)) {
            return $this->whereIn('is_active', array_map('boolval', $statuses));
        }
        return $this->where('is_active', (bool) $statuses);
    }

    /**
     * Filter categories with minimum product count
     */
    public function minProductCount($count)
    {
        return $this->withCount('products')->having('products_count', '>=', $count);
    }

    /**
     * Filter categories with maximum product count
     */
    public function maxProductCount($count)
    {
        return $this->withCount('products')->having('products_count', '<=', $count);
    }

    /**
     * Filter by category IDs
     */
    public function ids($ids)
    {
        if (is_array($ids)) {
            return $this->whereIn('id', $ids);
        }
        return $this->where('id', $ids);
    }

    /**
     * Exclude specific category IDs
     */
    public function excludeIds($ids)
    {
        if (is_array($ids)) {
            return $this->whereNotIn('id', $ids);
        }
        return $this->where('id', '!=', $ids);
    }

    /**
     * Filter by updated date range
     */
    public function updatedFrom($date)
    {
        return $this->where('updated_at', '>=', $date);
    }

    public function updatedTo($date)
    {
        return $this->where('updated_at', '<=', $date);
    }

    /**
     * Filter categories that have children
     */
    public function hasChildren($hasChildren = true)
    {
        if ($hasChildren) {
            return $this->whereHas('children');
        } else {
            return $this->whereDoesntHave('children');
        }
    }

    /**
     * Filter categories by specific parent names
     */
    public function parentName($parentName)
    {
        return $this->whereHas('parent.translations', function ($q) use ($parentName) {
            $q->where('content', 'like', '%' . $parentName . '%');
        });
    }

    /**
     * Combined filter for active categories with products
     */
    public function activeWithProducts()
    {
        return $this->where('is_active', true)->whereHas('products');
    }

    /**
     * Filter for featured/priority categories
     */
    public function featured($priority = 1)
    {
        return $this->where('priority', '<=', $priority)->where('is_active', true);
    }
}

