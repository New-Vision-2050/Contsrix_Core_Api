<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Coupon\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class CouponFilter extends SearchModelFilter
{
    public $relations = [];

    public function title($title)
    {
        return $this->where('title', 'like', "%{$title}%");
    }

    public function code($code)
    {
        return $this->where('code', 'like', "%{$code}%");
    }

    public function couponType($type)
    {
        return $this->where('coupon_type', $type);
    }

    public function isActive($status)
    {
        return $this->where('is_active', (bool) $status);
    }

    public function discountType($type)
    {
        return $this->where('discount_type', $type);
    }

    public function companyId($companyId)
    {
        return $this->where('company_id', $companyId);
    }

    public function customerId($customerId)
    {
        return $this->where('customer_id', $customerId);
    }

    public function validFrom($date)
    {
        return $this->where('start_date', '>=', $date);
    }

    public function validTo($date)
    {
        return $this->where('expire_date', '<=', $date);
    }

    public function active()
    {
        return $this->where('is_active', true);
    }

    public function inactive()
    {
        return $this->where('is_active', false);
    }
}
