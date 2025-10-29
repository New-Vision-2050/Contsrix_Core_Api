<?php

declare(strict_types=1);

namespace Modules\Ecommerce\DealDay\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;
use Carbon\Carbon;

class DealDayFilter extends SearchModelFilter
{
    public $relations = ['company', 'product'];

    /**
     * Filter by deal day name
     */
    public function name($name)
    {
        return $this->whereHas('translations', function ($translationQuery) use ($name) {
            $translationQuery->where('content', 'like', '%' . $name . '%');
        });
    }

    /**
     * General search filter (searches in name and related product name)
     */
    public function search($search)
    {
        return $this->where(function ($query) use ($search) {
            $query->whereHas('translations', function ($translationQuery) use ($search) {
                      $translationQuery->where('content', 'like', '%' . $search . '%');
                  })
                  ->orWhere('discount_type', 'like', '%' . $search . '%')
                  ->orWhereHas('product', function ($productQuery) use ($search) {
                      $productQuery->whereHas('translations', function ($translationQuery) use ($search) {
                          $translationQuery->where('content', 'like', '%' . $search . '%');
                      })
                                   ->orWhere('sku', 'like', '%' . $search . '%');
                  })
                  ->orWhereHas('company', function ($companyQuery) use ($search) {
                      $companyQuery->whereHas('translations', function ($translationQuery) use ($search) {
                          $translationQuery->where('content', 'like', '%' . $search . '%');
                      });
                  });
        });
    }

    /**
     * Filter by company ID
     */
    public function companyId($companyId)
    {
        return $this->where('company_id', $companyId);
    }

    /**
     * Filter by product ID
     */
    public function productId($productId)
    {
        return $this->where('product_id', $productId);
    }

    /**
     * Filter by discount type
     */
    public function discountType($discountType)
    {
        return $this->where('discount_type', $discountType);
    }

    /**
     * Filter by minimum discount value
     */
    public function minDiscountValue($minValue)
    {
        return $this->where('discount_value', '>=', $minValue);
    }

    /**
     * Filter by maximum discount value
     */
    public function maxDiscountValue($maxValue)
    {
        return $this->where('discount_value', '<=', $maxValue);
    }

    /**
     * Filter by active status
     */
    public function isActive($isActive)
    {
        return $this->where('is_active', (bool) $isActive);
    }

    /**
     * Filter by active deals only
     */
    public function activeOnly($activeOnly = true)
    {
        if ($activeOnly) {
            return $this->where('is_active', true);
        }
        return $this;
    }

    /**
     * Filter by inactive deals only
     */
    public function inactiveOnly($inactiveOnly = true)
    {
        if ($inactiveOnly) {
            return $this->where('is_active', false);
        }
        return $this;
    }

    /**
     * Filter by creation date range
     */
    public function createdFrom($date)
    {
        return $this->whereDate('created_at', '>=', Carbon::parse($date));
    }

    public function createdTo($date)
    {
        return $this->whereDate('created_at', '<=', Carbon::parse($date));
    }

    /**
     * Filter by update date range
     */
    public function updatedFrom($date)
    {
        return $this->whereDate('updated_at', '>=', Carbon::parse($date));
    }

    public function updatedTo($date)
    {
        return $this->whereDate('updated_at', '<=', Carbon::parse($date));
    }

    /**
     * Order by discount value
     */
    public function orderByDiscountValue($direction = 'asc')
    {
        return $this->orderBy('discount_value', $direction);
    }

    /**
     * Order by creation date
     */
    public function orderByCreated($direction = 'desc')
    {
        return $this->orderBy('created_at', $direction);
    }

    /**
     * Order by name
     */
    public function orderByName($direction = 'asc')
    {
        return $this->orderBy('name', $direction);
    }
}
