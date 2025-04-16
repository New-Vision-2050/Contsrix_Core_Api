<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserStatus\Handlers;

use Modules\UserInfo\UserStatus\Commands\UpdateUserStatusCommand;
use Modules\UserInfo\UserStatus\Repositories\UserStatusRepository;

class UpdateUserStatusHandler
{
    public function __construct(
        private UserStatusRepository $repository,
    ) {
    }

    public function handle(UpdateUserStatusCommand $updateUserStatusCommand)
    {
        $this->repository->updateUserStatus($updateUserStatusCommand->companyUserId, $updateUserStatusCommand->toArray());
    }
}
