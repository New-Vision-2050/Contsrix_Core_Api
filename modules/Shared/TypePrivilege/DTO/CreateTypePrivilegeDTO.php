<?php

declare(strict_types=1);

namespace Modules\Shared\TypePrivilege\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateTypePrivilegeDTO
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
