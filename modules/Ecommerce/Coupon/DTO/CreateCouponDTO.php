<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Coupon\DTO;

class CreateCouponDTO
{
    public function __construct(
        public readonly string $companyId,
        public readonly string $couponType,
        public readonly string $title,
        public readonly string $code,
        public readonly ?string $customerId,
        public readonly ?int $maxUsagePerUser,
        public readonly string $discountType,
        public readonly float $discountAmount,
        public readonly float $minPurchase,
        public readonly float $maxDiscount,
        public readonly string $startDate,
        public readonly string $expireDate,
        public readonly bool $isActive,
    ) {
    }

    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId,
            'coupon_type' => $this->couponType,
            'title' => $this->title,
            'code' => $this->code,
            'customer_id' => $this->customerId,
            'max_usage_per_user' => $this->maxUsagePerUser,
            'discount_type' => $this->discountType,
            'discount_amount' => $this->discountAmount,
            'min_purchase' => $this->minPurchase,
            'max_discount' => $this->maxDiscount,
            'start_date' => $this->startDate,
            'expire_date' => $this->expireDate,
            'is_active' => $this->isActive,
        ];
    }
}
