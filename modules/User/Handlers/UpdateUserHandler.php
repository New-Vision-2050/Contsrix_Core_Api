<?php

declare(strict_types=1);

namespace Modules\User\Handlers;

use Modules\User\Commands\UpdateUserCommand;
use Modules\User\Repositories\UserRepository;

class UpdateUserHandler
{
    public function __construct(
        private UserRepository $repository,
    ) {
    }

    public function handle(UpdateUserCommand $updateUserCommand)
    {
        $this->repository->updateUser($updateUserCommand->getId(), $updateUserCommand->toArray());
    }
}
