<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoPayment\DTO;

use Ramsey\Uuid\UuidInterface;

class UpsertEcoPaymentDTO
{
    public function __construct(
        public UuidInterface $companyId,
        public string $paymentId,
        public bool $isDefault = false,
        public bool $isActive = true,
    ) {
    }

    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId->toString(),
            'payment_id' => $this->paymentId,
            'is_default' => $this->isDefault,
            'is_active' => $this->isActive,
        ];
    }
}
