<?php

declare(strict_types=1);

namespace Modules\Test\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateTestDTO
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
