<?php

declare(strict_types=1);

namespace Modules\Shared\MaritalStatus\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateMaritalStatusDTO
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
