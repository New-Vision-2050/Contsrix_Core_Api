<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Handlers;

use Modules\Company\ManagementHierarchy\Commands\UpdateBranchCommand;
use Modules\Company\ManagementHierarchy\Commands\UpdateManagementHierarchyCommand;
use Modules\Company\ManagementHierarchy\Repositories\ManagementHierarchyRepository;

class UpdateBranchHandler
{
    public function __construct(
        private ManagementHierarchyRepository $repository,
    ) {
    }

    public function handle(UpdateBranchCommand $updateBranchCommand)
    {
        $this->repository->updateManagementHierarchy($updateBranchCommand->getId(), $updateBranchCommand->toArray(), $updateBranchCommand->addressToArray());
    }
}
