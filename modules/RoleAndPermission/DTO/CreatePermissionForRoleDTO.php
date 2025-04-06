<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\DTO;

use Ramsey\Uuid\UuidInterface;

class CreatePermissionForRoleDTO
{
    public function __construct(
        public array $permissions,
    ) {
    }

    public function toArray(): array
    {
        return $this->permissions;
    }
}
