<?php

declare(strict_types=1);

namespace Modules\Unit\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateUnitDTO
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
