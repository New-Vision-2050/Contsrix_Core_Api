<?php

declare(strict_types=1);

namespace Modules\User\Handlers;

use Modules\User\Repositories\UserRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteUserHandler
{
    public function __construct(
        private UserRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteUser($id);
    }
}
