<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserPrivilege\Handlers;

use Modules\UserInfo\UserPrivilege\Repositories\UserPrivilegeRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteUserPrivilegeHandler
{
    public function __construct(
        private UserPrivilegeRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteUserPrivilege($id);
    }
}
