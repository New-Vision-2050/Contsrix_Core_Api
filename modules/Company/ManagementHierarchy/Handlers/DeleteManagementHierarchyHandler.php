<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Handlers;

use Modules\Company\ManagementHierarchy\Repositories\ManagementHierarchyRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteManagementHierarchyHandler
{
    public function __construct(
        private ManagementHierarchyRepository $repository,
    ) {
    }

    public function handle(int $id)
    {
        $this->repository->deleteManagementHierarchy($id);
    }
}
