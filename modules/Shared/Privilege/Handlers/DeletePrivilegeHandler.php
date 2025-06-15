<?php

declare(strict_types=1);

namespace Modules\Shared\Privilege\Handlers;

use Modules\Shared\Privilege\Repositories\PrivilegeRepository;
use Ramsey\Uuid\UuidInterface;

class DeletePrivilegeHandler
{
    public function __construct(
        private PrivilegeRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deletePrivilege($id);
    }
}
