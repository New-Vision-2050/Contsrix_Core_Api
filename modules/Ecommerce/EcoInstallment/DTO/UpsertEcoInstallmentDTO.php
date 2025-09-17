<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoInstallment\DTO;

use Ramsey\Uuid\UuidInterface;

class UpsertEcoInstallmentDTO
{
    public function __construct(
        public UuidInterface $companyId,
        public string $installmentId,
        public bool $isDefault = false,
        public bool $isActive = true,
    ) {
    }

    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId->toString(),
            'installment_id' => $this->installmentId,
            'is_default' => $this->isDefault,
            'is_active' => $this->isActive,
        ];
    }
}
