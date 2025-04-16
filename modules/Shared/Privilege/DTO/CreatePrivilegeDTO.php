<?php

declare(strict_types=1);

namespace Modules\Shared\Privilege\DTO;

use Ramsey\Uuid\UuidInterface;

class CreatePrivilegeDTO
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
