<?php

declare(strict_types=1);

namespace Modules\Ecommerce\DealDay\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateDealDayDTO
{
    public function __construct(
        public readonly UuidInterface $companyId,
        public readonly array $name,
        public readonly UuidInterface $productId,
        public readonly string $discountType,
        public readonly float $discountValue,
        public readonly bool $isActive = true,
    ) {
    }

    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId->toString(),
            'name' => $this->name,
            'product_id' => $this->productId->toString(),
            'discount_type' => $this->discountType,
            'discount_value' => $this->discountValue,
            'is_active' => $this->isActive,
        ];
    }
}
