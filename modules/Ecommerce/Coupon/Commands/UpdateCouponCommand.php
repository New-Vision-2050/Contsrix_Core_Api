<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Coupon\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateCouponCommand
{
    public function __construct(
        public readonly UuidInterface $id,
        public readonly ?string $couponType = null,
        public readonly ?string $title = null,
        public readonly ?string $code = null,
        public readonly ?string $customerId = null,
        public readonly ?int $maxUsagePerUser = null,
        public readonly ?string $discountType = null,
        public readonly ?float $discountAmount = null,
        public readonly ?float $minPurchase = null,
        public readonly ?float $maxDiscount = null,
        public readonly ?string $startDate = null,
        public readonly ?string $expireDate = null,
        public readonly ?bool $isActive = null,
    ) {
    }

    public function toArray(): array
    {
        $data = [];

        if ($this->couponType !== null) {
            $data['coupon_type'] = $this->couponType;
        }

        if ($this->title !== null) {
            $data['title'] = $this->title;
        }

        if ($this->code !== null) {
            $data['code'] = $this->code;
        }

        if ($this->customerId !== null) {
            $data['customer_id'] = $this->customerId;
        }

        if ($this->maxUsagePerUser !== null) {
            $data['max_usage_per_user'] = $this->maxUsagePerUser;
        }

        if ($this->discountType !== null) {
            $data['discount_type'] = $this->discountType;
        }

        if ($this->discountAmount !== null) {
            $data['discount_amount'] = $this->discountAmount;
        }

        if ($this->minPurchase !== null) {
            $data['min_purchase'] = $this->minPurchase;
        }

        if ($this->maxDiscount !== null) {
            $data['max_discount'] = $this->maxDiscount;
        }

        if ($this->startDate !== null) {
            $data['start_date'] = $this->startDate;
        }

        if ($this->expireDate !== null) {
            $data['expire_date'] = $this->expireDate;
        }

        if ($this->isActive !== null) {
            $data['is_active'] = $this->isActive;
        }

        return $data;
    }
}
