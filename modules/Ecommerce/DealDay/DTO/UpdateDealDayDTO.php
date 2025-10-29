<?php

declare(strict_types=1);

namespace Modules\Ecommerce\DealDay\DTO;

use Ramsey\Uuid\UuidInterface;

class UpdateDealDayDTO
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?UuidInterface $productId = null,
        public readonly ?string $discountType = null,
        public readonly ?float $discountValue = null,
        public readonly ?bool $isActive = null,
    ) {
    }

    public function toArray(): array
    {
        $data = [];

        if ($this->name !== null) {
            $data['name'] = $this->name;
        }

        if ($this->productId !== null) {
            $data['product_id'] = $this->productId->toString();
        }

        if ($this->discountType !== null) {
            $data['discount_type'] = $this->discountType;
        }

        if ($this->discountValue !== null) {
            $data['discount_value'] = $this->discountValue;
        }

        if ($this->isActive !== null) {
            $data['is_active'] = $this->isActive;
        }

        return $data;
    }
}
