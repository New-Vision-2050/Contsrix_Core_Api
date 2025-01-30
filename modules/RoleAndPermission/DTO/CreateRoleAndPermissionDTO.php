<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateRoleAndPermissionDTO
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
