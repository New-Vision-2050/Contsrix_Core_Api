<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserRelative\Handlers;

use Modules\UserInfo\UserRelative\Commands\UpdateUserRelativeCommand;
use Modules\UserInfo\UserRelative\Repositories\UserRelativeRepository;

class UpdateUserRelativeHandler
{
    public function __construct(
        private UserRelativeRepository $repository,
    ) {
    }

    public function handle(UpdateUserRelativeCommand $updateUserRelativeCommand)
    {
        $this->repository->updateUserRelative($updateUserRelativeCommand->getId(), $updateUserRelativeCommand->toArray());
    }
}
