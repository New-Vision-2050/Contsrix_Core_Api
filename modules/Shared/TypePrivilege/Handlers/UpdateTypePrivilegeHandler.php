<?php

declare(strict_types=1);

namespace Modules\Shared\TypePrivilege\Handlers;

use Modules\Shared\TypePrivilege\Commands\UpdateTypePrivilegeCommand;
use Modules\Shared\TypePrivilege\Repositories\TypePrivilegeRepository;

class UpdateTypePrivilegeHandler
{
    public function __construct(
        private TypePrivilegeRepository $repository,
    ) {
    }

    public function handle(UpdateTypePrivilegeCommand $updateTypePrivilegeCommand)
    {
        $this->repository->updateTypePrivilege($updateTypePrivilegeCommand->getId(), $updateTypePrivilegeCommand->toArray());
    }
}
