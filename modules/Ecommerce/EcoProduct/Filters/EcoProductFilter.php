<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class EcoProductFilter extends SearchModelFilter
{
    public $relations = ['category', 'translations', 'warehouse'];

    /**
     * Filter by product name (search in translations)
     */
    public function name($name)
    {
        return $this->whereHas('translations', function ($q) use ($name) {
            $q->where('content', 'like', '%' . $name . '%');
        });
    }

    /**
     * Filter by product description (search in translations)
     */
    public function description($description)
    {
        return $this->whereHas('translations', function ($q) use ($description) {
            $q->where('content', 'like', '%' . $description . '%');
        });
    }

    /**
     * General search filter (searches in name, description, sku, price, and status)
     */
    public function search($search)
    {
        return $this->where(function ($query) use ($search) {
            // Search in SKU
            $query->where('sku', 'like', '%' . $search . '%')
                  // Search in price (exact match and partial)
                  ->orWhere('price', 'like', '%' . $search . '%')
                  // Search in translations (name, description)
                  ->orWhereHas('translations', function ($q) use ($search) {
                      $q->where('content', 'like', '%' . $search . '%');
                  });

            // Handle status search terms (Arabic and English)
            $searchLower = strtolower(trim($search));

            // Arabic status terms
            if (in_array($searchLower, ['نشط', 'نشطة', 'مفعل', 'مفعلة'])) {
                $query->orWhere('is_visible', true);
            } elseif (in_array($searchLower, ['غير نشط', 'غير نشطة', 'غير مفعل', 'غير مفعلة', 'معطل', 'معطلة'])) {
                $query->orWhere('is_visible', false);
            }

            // English status terms
            elseif (in_array($searchLower, ['active', 'enabled', 'visible', 'published'])) {
                $query->orWhere('is_visible', true);
            } elseif (in_array($searchLower, ['inactive', 'disabled', 'hidden', 'unpublished', 'in_active'])) {
                $query->orWhere('is_visible', false);
            }

            // Stock status terms (Arabic)
            elseif (in_array($searchLower, ['متوفر', 'في المخزن', 'موجود'])) {
                $query->orWhere('stock', '>', 0);
            } elseif (in_array($searchLower, ['غير متوفر', 'نفد', 'نفذ', 'غير موجود'])) {
                $query->orWhere('stock', '<=', 0);
            } elseif (in_array($searchLower, ['مخزون قليل', 'قليل', 'منخفض'])) {
                $query->orWhere(function ($q) {
                    $q->where('stock', '>', 0)->where('stock', '<=', 10);
                });
            }

            // Stock status terms (English)
            elseif (in_array($searchLower, ['in stock', 'available', 'in_stock'])) {
                $query->orWhere('stock', '>', 0);
            } elseif (in_array($searchLower, ['out of stock', 'unavailable', 'out_of_stock'])) {
                $query->orWhere('stock', '<=', 0);
            } elseif (in_array($searchLower, ['low stock', 'low_stock'])) {
                $query->orWhere(function ($q) {
                    $q->where('stock', '>', 0)->where('stock', '<=', 10);
                });
            }

            // Numeric search for exact price matches
            elseif (is_numeric($search)) {
                $numericValue = (float) $search;
                $query->orWhere('price', $numericValue)
                      ->orWhere('stock', $numericValue);
            }
        });
    }

    /**
     * Filter by category ID
     */
    public function category($categoryId)
    {
        return $this->where('category_id', $categoryId);
    }

    /**
     * Filter by multiple category IDs
     */
    public function categories($categoryIds)
    {
        if (is_array($categoryIds)) {
            return $this->whereIn('category_id', $categoryIds);
        }
        return $this->where('category_id', $categoryIds);
    }

    /**
     * Filter by product status (active/inactive)
     */
    public function status($status)
    {
        if ($status === 'active') {
            return $this->where('is_visible', true);
        } elseif ($status === 'inactive') {
            return $this->where('is_visible', false);
        }
        return $this->where('is_visible', (bool) $status);
    }

    /**
     * Filter by price range
     */
    public function priceFrom($price)
    {
        return $this->where('price', '>=', $price);
    }

    public function priceTo($price)
    {
        return $this->where('price', '<=', $price);
    }

    public function priceRange($range)
    {
        if (is_array($range) && count($range) === 2) {
            return $this->whereBetween('price', [$range[0], $range[1]]);
        }
        return $this;
    }

    /**
     * Filter by stock quantity
     */
    public function stockFrom($quantity)
    {
        return $this->where('stock', '>=', $quantity);
    }

    public function stockTo($quantity)
    {
        return $this->where('stock', '<=', $quantity);
    }

    /**
     * Filter by stock status
     */
    public function stockStatus($status)
    {
        switch ($status) {
            case 'in_stock':
                return $this->where('stock', '>', 0);
            case 'out_of_stock':
                return $this->where('stock', '<=', 0);
            case 'low_stock':
                return $this->where('stock', '>', 0)
                           ->where('stock', '<=', 10); // Assuming low stock threshold is 10
            default:
                return $this;
        }
    }

    /**
     * Filter by warehouse ID
     */
    public function warehouse($warehouseId)
    {
        return $this->where('warehouse_id', $warehouseId);
    }

    /**
     * Filter by product type
     */
    public function type($type)
    {
        return $this->where('type', $type);
    }

    /**
     * Filter by featured products
     */
    public function featured($featured = true)
    {
        return $this->where('is_featured', (bool) $featured);
    }

    /**
     * Filter by discount availability
     */
    public function hasDiscount($hasDiscount = true)
    {
        if ($hasDiscount) {
            return $this->where('discount_price', '>', 0)
                       ->whereNotNull('discount_price');
        } else {
            return $this->where(function ($query) {
                $query->where('discount_price', '<=', 0)
                      ->orWhereNull('discount_price');
            });
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
     * Filter by weight range
     */
    public function weightFrom($weight)
    {
        return $this->where('weight', '>=', $weight);
    }

    public function weightTo($weight)
    {
        return $this->where('weight', '<=', $weight);
    }

    /**
     * Filter by brand
     */
    public function brand($brand)
    {
        return $this->where('brand', 'like', '%' . $brand . '%');
    }

    /**
     * Filter by tags
     */
    public function tags($tags)
    {
        if (is_array($tags)) {
            return $this->where(function ($query) use ($tags) {
                foreach ($tags as $tag) {
                    $query->orWhere('tags', 'like', '%' . $tag . '%');
                }
            });
        }
        return $this->where('tags', 'like', '%' . $tags . '%');
    }

    /**
     * Filter by minimum order quantity
     */
    public function minOrderQuantity($quantity)
    {
        return $this->where('min_order_quantity', '<=', $quantity);
    }

    /**
     * Sort by popularity (based on order count or views)
     */
    public function sortByPopularity($direction = 'desc')
    {
        return $this->orderBy('view_count', $direction);
    }

    /**
     * Sort by newest first
     */
    public function sortByNewest()
    {
        return $this->orderBy('created_at', 'desc');
    }

    /**
     * Sort by price
     */
    public function sortByPrice($direction = 'asc')
    {
        return $this->orderBy('price', $direction);
    }

    /**
     * Sort by stock quantity
     */
    public function sortByStock($direction = 'desc')
    {
        return $this->orderBy('stock', $direction);
    }
}
