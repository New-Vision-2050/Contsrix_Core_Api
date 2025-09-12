<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoDiscount\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateEcoDiscountDTO
{
    public function __construct(
        public string $name,
        public ?string $description = null,
        public ?string $code = null,
        public string $type = 'percentage',
        public float $value = 0,
        public ?float $min_order_amount = null,
        public ?float $max_discount_amount = null,
        public ?int $usage_limit = null,
        public ?string $start_date = null,
        public ?string $end_date = null,
        public bool $is_active = true,
        public string $applies_to = 'all_products',
        public array $product_ids = [],
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'code' => $this->code ?: $this->generateCode(),
            'type' => $this->type,
            'value' => $this->value,
            'min_order_amount' => $this->min_order_amount,
            'max_discount_amount' => $this->max_discount_amount,
            'usage_limit' => $this->usage_limit,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'is_active' => $this->is_active,
            'applies_to' => $this->applies_to,
            'product_ids' => $this->product_ids,
        ];
    }

    private function generateCode(): string
    {
        return 'DISC' . strtoupper(substr(md5(uniqid()), 0, 6));
    }
}
