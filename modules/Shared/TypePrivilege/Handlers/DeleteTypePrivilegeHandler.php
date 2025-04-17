<?php

declare(strict_types=1);

namespace Modules\Shared\TypePrivilege\Handlers;

use Modules\Shared\TypePrivilege\Repositories\TypePrivilegeRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteTypePrivilegeHandler
{
    public function __construct(
        private TypePrivilegeRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteTypePrivilege($id);
    }
}
