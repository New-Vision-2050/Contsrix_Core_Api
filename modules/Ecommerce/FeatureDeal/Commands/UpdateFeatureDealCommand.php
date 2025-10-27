<?php

declare(strict_types=1);

namespace Modules\Ecommerce\FeatureDeal\Commands;

use Ramsey\Uuid\UuidInterface;
use Carbon\Carbon;

class UpdateFeatureDealCommand
{
    public function __construct(
        private UuidInterface $id,
        private ?array $name = null,
        private ?Carbon $startDate = null,
        private ?Carbon $endDate = null,
        private ?string $discountType = null,
        private ?float $discountValue = null,
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

    public function getStartDate(): ?Carbon
    {
        return $this->startDate;
    }

    public function getEndDate(): ?Carbon
    {
        return $this->endDate;
    }

    public function getDiscountType(): ?string
    {
        return $this->discountType;
    }

    public function getDiscountValue(): ?float
    {
        return $this->discountValue;
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

        if ($this->startDate !== null) {
            $data['start_date'] = $this->startDate->toDateString();
        }

        if ($this->endDate !== null) {
            $data['end_date'] = $this->endDate->toDateString();
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
