<?php

declare(strict_types=1);

namespace Modules\Shared\Payment\DTO;

use Ramsey\Uuid\UuidInterface;

class CreatePaymentDTO
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
