<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoInstallment\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateEcoInstallmentDTO
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
