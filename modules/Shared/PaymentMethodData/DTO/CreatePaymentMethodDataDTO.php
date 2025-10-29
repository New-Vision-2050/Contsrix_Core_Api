<?php

declare(strict_types=1);

namespace Modules\Shared\PaymentMethodData\DTO;

use Ramsey\Uuid\UuidInterface;

readonly class CreatePaymentMethodDataDTO
{
    public function __construct(
        public string $type,
        public array $name, // Changed to array for translations
        public bool $isActive = true,
    ) {
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'name' => $this->name,
            'is_active' => $this->isActive,
        ];
    }
}
