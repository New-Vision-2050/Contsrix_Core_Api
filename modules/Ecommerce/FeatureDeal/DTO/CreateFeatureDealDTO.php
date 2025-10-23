<?php

declare(strict_types=1);

namespace Modules\Ecommerce\FeatureDeal\DTO;

use Ramsey\Uuid\UuidInterface;
use Carbon\Carbon;

class CreateFeatureDealDTO
{
    public function __construct(
        public readonly UuidInterface $companyId,
        public readonly array $name,
        public readonly Carbon $startDate,
        public readonly Carbon $endDate,
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
            'start_date' => $this->startDate->toDateString(),
            'end_date' => $this->endDate->toDateString(),
            'discount_type' => $this->discountType,
            'discount_value' => $this->discountValue,
            'is_active' => $this->isActive,
        ];
    }
}
