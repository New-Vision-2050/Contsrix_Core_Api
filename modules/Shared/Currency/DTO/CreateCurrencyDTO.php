<?php

declare(strict_types=1);

namespace Modules\Shared\Currency\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateCurrencyDTO
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
