<?php

declare(strict_types=1);

namespace Modules\Ecommerce\DealDay\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateDealDayCommand
{
    public function __construct(
        private UuidInterface $id,
        private ?array $name = null,
        private ?UuidInterface $productId = null,
        private ?string $discountType = null,
        private ?float $discountValue = null,
        private ?string $dateOffer = null,
        private ?bool $isActive = null,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getName(): ?array
    {
        return $this->name;
    }

    public function getProductId(): ?UuidInterface
    {
        return $this->productId;
    }

    public function getDiscountType(): ?string
    {
        return $this->discountType;
    }

    public function getDiscountValue(): ?float
    {
        return $this->discountValue;
    }

    public function getDateOffer(): ?string
    {
        return $this->dateOffer;
    }

    public function getIsActive(): ?bool
    {
        return $this->isActive;
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

        if ($this->dateOffer !== null) {
            $data['date_offer'] = $this->dateOffer;
        }

        if ($this->isActive !== null) {
            $data['is_active'] = $this->isActive;
        }

        return $data;
    }
}
