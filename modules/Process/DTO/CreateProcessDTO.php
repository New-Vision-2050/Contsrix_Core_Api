<?php

declare(strict_types=1);

namespace Modules\Process\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateProcessDTO
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
