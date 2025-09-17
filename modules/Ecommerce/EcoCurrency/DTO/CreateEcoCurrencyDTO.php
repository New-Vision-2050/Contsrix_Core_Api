<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoCurrency\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateEcoCurrencyDTO
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
