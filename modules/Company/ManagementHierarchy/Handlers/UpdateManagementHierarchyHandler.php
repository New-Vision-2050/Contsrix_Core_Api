<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Handlers;

use Modules\Company\ManagementHierarchy\Commands\UpdateManagementHierarchyCommand;
use Modules\Company\ManagementHierarchy\Repositories\ManagementHierarchyRepository;

class UpdateManagementHierarchyHandler
{
    public function __construct(
        private ManagementHierarchyRepository $repository,
    ) {
    }

    public function handle(UpdateManagementHierarchyCommand $updateManagementHierarchyCommand)
    {
        $this->repository->updateManagementHierarchy($updateManagementHierarchyCommand->getId(), $updateManagementHierarchyCommand->toArray());
    }
}
