<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Commands;

use Ramsey\Uuid\UuidInterface;

class DeleteRoleForUserCommand
{
    public function __construct(
        private UuidInterface $user_id,
        private int $role,
    ) {
    }

    public function getUserId(): UuidInterface
    {
        return $this->user_id;
    }

    public function getRole(): int
    {
        return $this->role;
    }
}
