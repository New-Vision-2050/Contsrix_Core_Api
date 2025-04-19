<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserStatus\Handlers;

use Modules\UserInfo\UserStatus\Repositories\UserStatusRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteUserStatusHandler
{
    public function __construct(
        private UserStatusRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteUserStatus($id);
    }
}
