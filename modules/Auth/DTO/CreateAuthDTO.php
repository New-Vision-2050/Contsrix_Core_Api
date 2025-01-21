<?php

declare(strict_types=1);

namespace Modules\Auth\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateAuthDTO
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
