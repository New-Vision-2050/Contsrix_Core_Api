<?php

declare(strict_types=1);

namespace Modules\Ecommerce\OrderTransaction\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateOrderTransactionDTO
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
