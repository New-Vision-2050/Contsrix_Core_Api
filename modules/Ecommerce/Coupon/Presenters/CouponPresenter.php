<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Coupon\Presenters;

use Modules\Ecommerce\Coupon\Models\Coupon;
use BasePackage\Shared\Presenters\AbstractPresenter;

class CouponPresenter extends AbstractPresenter
{
    private Coupon $coupon;

    public function __construct(Coupon $coupon)
    {
        $this->coupon = $coupon;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->coupon->id,
            'company_id' => $this->coupon->company_id,
            'coupon_type' => $this->coupon->coupon_type,
            'title' => $this->coupon->title,
            'code' => $this->coupon->code,
            'customer_id' => $this->coupon->customer_id,
            'max_usage_per_user' => $this->coupon->max_usage_per_user,
            'discount_type' => $this->coupon->discount_type,
            'discount_amount' => $this->coupon->discount_amount,
            'min_purchase' => $this->coupon->min_purchase,
            'max_discount' => $this->coupon->max_discount,
            'start_date' => $this->coupon->start_date?->format('Y-m-d'),
            'expire_date' => $this->coupon->expire_date?->format('Y-m-d'),
            'is_active' => (int) $this->coupon->is_active,

        ];
    }
}
