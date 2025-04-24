<?php

declare(strict_types=1);

namespace Modules\Shared\Period\DTO;

use Ramsey\Uuid\UuidInterface;

class CreatePeriodDTO
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
