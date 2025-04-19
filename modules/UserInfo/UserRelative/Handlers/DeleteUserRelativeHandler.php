<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserRelative\Handlers;

use Modules\UserInfo\UserRelative\Repositories\UserRelativeRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteUserRelativeHandler
{
    public function __construct(
        private UserRelativeRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteUserRelative($id);
    }
}
