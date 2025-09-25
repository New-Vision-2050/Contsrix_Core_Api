<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoPayment\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateEcoPaymentDTO
{
    public function __construct(
        public string $name,
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
        ];
    }
}
