<?php

declare(strict_types=1);

namespace Modules\User\Commands;

use Ramsey\Uuid\UuidInterface;

class AssignRoleForUserCommand
{
    public function __construct(
        private UuidInterface $id,
        private array $roles,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function geRoles(): ?array
    {
        return $this->roles;
    }
}
