<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoDiscount\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateEcoDiscountProductCommand
{
    public function __construct(
        private UuidInterface $id,
        private ?bool $hasDiscount = null,
        private ?float $discountAmount = null,
        private ?float $discountPercentage = null,
        private ?string $discountStartDate = null,
        private ?string $discountEndDate = null,
        private ?float $maxDiscountAmount = null,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getHasDiscount(): ?bool
    {
        return $this->hasDiscount;
    }

    public function getDiscountAmount(): ?float
    {
        return $this->discountAmount;
    }

    public function getDiscountPercentage(): ?float
    {
        return $this->discountPercentage;
    }

    public function getDiscountStartDate(): ?string
    {
        return $this->discountStartDate;
    }

    public function getDiscountEndDate(): ?string
    {
        return $this->discountEndDate;
    }

    public function getMaxDiscountAmount(): ?float
    {
        return $this->maxDiscountAmount;
    }

    public function toArray(): array
    {
        $data = [];
        
        if ($this->hasDiscount !== null) {
            $data['has_discount'] = $this->hasDiscount;
        }
        
        if ($this->discountAmount !== null) {
            $data['discount_amount'] = $this->discountAmount;
        }
        
        if ($this->discountPercentage !== null) {
            $data['discount_percentage'] = $this->discountPercentage;
        }
        
        if ($this->discountStartDate !== null) {
            $data['discount_start_date'] = $this->discountStartDate;
        }
        
        if ($this->discountEndDate !== null) {
            $data['discount_end_date'] = $this->discountEndDate;
        }
        
        if ($this->maxDiscountAmount !== null) {
            $data['max_discount_amount'] = $this->maxDiscountAmount;
        }

        return $data;
    }
}
