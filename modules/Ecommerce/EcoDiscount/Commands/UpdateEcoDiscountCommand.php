<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoDiscount\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateEcoDiscountCommand
{
    public function __construct(
        private UuidInterface $id,
        private string $name,
        private ?string $description = null,
        private ?string $code = null,
        private string $type = 'percentage',
        private float $value = 0,
        private ?float $min_order_amount = null,
        private ?float $max_discount_amount = null,
        private ?int $usage_limit = null,
        private ?string $start_date = null,
        private ?string $end_date = null,
        private bool $is_active = true,
        private string $applies_to = 'all_products',
        private array $product_ids = [],
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function getMinOrderAmount(): ?float
    {
        return $this->min_order_amount;
    }

    public function getMaxDiscountAmount(): ?float
    {
        return $this->max_discount_amount;
    }

    public function getUsageLimit(): ?int
    {
        return $this->usage_limit;
    }

    public function getStartDate(): ?string
    {
        return $this->start_date;
    }

    public function getEndDate(): ?string
    {
        return $this->end_date;
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function getAppliesTo(): string
    {
        return $this->applies_to;
    }

    public function getProductIds(): array
    {
        return $this->product_ids;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'code' => $this->code,
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
}
