<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Commands;

use Ramsey\Uuid\UuidInterface;

class AssignPermissionToRoleCommand
{
    public function __construct(
        private UuidInterface $id,
        private array $permissions,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getPermissions(): ?array
    {
        return $this->permissions;
    }


}
