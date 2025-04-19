<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserPrivilege\Handlers;

use Modules\UserInfo\UserPrivilege\Commands\UpdateUserPrivilegeCommand;
use Modules\UserInfo\UserPrivilege\Repositories\UserPrivilegeRepository;

class UpdateUserPrivilegeHandler
{
    public function __construct(
        private UserPrivilegeRepository $repository,
    ) {
    }

    public function handle(UpdateUserPrivilegeCommand $updateUserPrivilegeCommand)
    {
        $this->repository->updateUserPrivilege($updateUserPrivilegeCommand->getId(), $updateUserPrivilegeCommand->toArray());
    }
}
