<?php

declare(strict_types=1);

namespace Modules\Shared\Privilege\Handlers;

use Modules\Shared\Privilege\Commands\UpdatePrivilegeCommand;
use Modules\Shared\Privilege\Repositories\PrivilegeRepository;

class UpdatePrivilegeHandler
{
    public function __construct(
        private PrivilegeRepository $repository,
    ) {
    }

    public function handle(UpdatePrivilegeCommand $updatePrivilegeCommand)
    {
        $this->repository->updatePrivilege($updatePrivilegeCommand->getId(), $updatePrivilegeCommand->toArray());
    }
}
