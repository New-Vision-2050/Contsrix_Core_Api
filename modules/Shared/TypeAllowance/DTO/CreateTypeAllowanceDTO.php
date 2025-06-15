<?php

declare(strict_types=1);

namespace Modules\Shared\TypeAllowance\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateTypeAllowanceDTO
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
