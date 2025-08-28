<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoOrder\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateEcoOrderDTO
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
