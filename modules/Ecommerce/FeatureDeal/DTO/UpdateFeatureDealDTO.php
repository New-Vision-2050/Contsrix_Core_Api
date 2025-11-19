<?php

declare(strict_types=1);

namespace Modules\Ecommerce\FeatureDeal\DTO;

use Carbon\Carbon;

class UpdateFeatureDealDTO
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?Carbon $startDate = null,
        public readonly ?Carbon $endDate = null,
        public readonly ?string $discountType = null,
        public readonly ?float $discountValue = null,
        public readonly ?array $productIds = null,
        public readonly ?bool $isActive = null,
    ) {
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

    public function products(): ?array
    {
        return $this->productIds;
    }
}
