<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateRoleCommand
{
    public function __construct(
        private UuidInterface $id,
        private string $name,
        private ?array $permissions = null,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
        ]);
    }
    public function getPermissions(): ?array
    {
        return $this->permissions;
    }
}
