<?php

declare(strict_types=1);

namespace Modules\Ecommerce\FeatureDeal\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class FeatureDealFilter extends SearchModelFilter
{
    public $relations = ['company'];

    /**
     * Filter by feature deal name
     */
    public function name($name)
    {
        return $this->whereHas('translations', function ($translationQuery) use ($name) {
            $translationQuery->where('content', 'like', '%' . $name . '%');
        });
    }

    /**
     * General search filter (searches in name and related company name)
     */
    public function search($search)
    {
        return $this->where(function ($query) use ($search) {
            $query->whereHas('translations', function ($translationQuery) use ($search) {
                      $translationQuery->where('content', 'like', '%' . $search . '%');
                  })
                  ->orWhere('discount_type', 'like', '%' . $search . '%')
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
        return $this->where('is_active', $isActive);
    }

    /**
     * Filter by active deals only
     */
    public function activeOnly($activeOnly)
    {
        if ($activeOnly) {
            return $this->where('is_active', true);
        }
        return $this;
    }

    /**
     * Filter by inactive deals only
     */
    public function inactiveOnly($inactiveOnly)
    {
        if ($inactiveOnly) {
            return $this->where('is_active', false);
        }
        return $this;
    }

    /**
     * Filter by current deals (within date range)
     */
    public function currentOnly($currentOnly)
    {
        if ($currentOnly) {
            $today = now()->toDateString();
            return $this->where('start_date', '<=', $today)
                        ->where('end_date', '>=', $today)
                        ->where('is_active', true);
        }
        return $this;
    }

    /**
     * Filter by start date from
     */
    public function startDateFrom($startDate)
    {
        return $this->where('start_date', '>=', $startDate);
    }

    /**
     * Filter by start date to
     */
    public function startDateTo($startDate)
    {
        return $this->where('start_date', '<=', $startDate);
    }

    /**
     * Filter by end date from
     */
    public function endDateFrom($endDate)
    {
        return $this->where('end_date', '>=', $endDate);
    }

    /**
     * Filter by end date to
     */
    public function endDateTo($endDate)
    {
        return $this->where('end_date', '<=', $endDate);
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
