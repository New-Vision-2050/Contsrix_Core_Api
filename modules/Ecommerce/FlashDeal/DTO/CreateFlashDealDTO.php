<?php

declare(strict_types=1);

namespace Modules\Ecommerce\FlashDeal\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateFlashDealDTO
{
    public function __construct(
        public UuidInterface $companyId,
        public array $name,
        public string $startDate,
        public string $endDate,
        public bool $isActive = true,
    ) {
    }

    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId->toString(),
            'name' => $this->name,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'is_active' => $this->isActive,
        ];
    }
}
