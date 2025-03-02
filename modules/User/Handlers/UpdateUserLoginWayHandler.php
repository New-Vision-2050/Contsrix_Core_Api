<?php

declare(strict_types=1);

namespace Modules\User\Handlers;

use Modules\User\Commands\UpdateUserCommand;
use Modules\User\Commands\UpdateUserLoginWayCommand;
use Modules\User\Repositories\UserRepository;

class UpdateUserLoginWayHandler
{
    public function __construct(
        private UserRepository $repository,
    ) {
    }

    public function handle(UpdateUserLoginWayCommand $updateUserLoginWayCommand)
    {
        $this->repository->updateUser($updateUserLoginWayCommand->getId(), $updateUserLoginWayCommand->toArray());
    }
}
