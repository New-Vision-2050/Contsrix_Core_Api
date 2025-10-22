<?php

declare(strict_types=1);

namespace Modules\Ecommerce\FlashDeal\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateFlashDealCommand
{
    public function __construct(
        public readonly UuidInterface $id,
        public readonly ?array $name = null,
        public readonly ?string $startDate = null,
        public readonly ?string $endDate = null,
        public readonly ?bool $isActive = null,
    ) {
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'is_active' => $this->isActive,
        ], fn($value) => $value !== null);
    }
}
