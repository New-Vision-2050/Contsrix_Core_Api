<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Handlers;

use Modules\Company\ManagementHierarchy\Commands\MakeBranchMainCommand;
use Modules\Company\ManagementHierarchy\Commands\UpdateManagementHierarchyCommand;
use Modules\Company\ManagementHierarchy\Repositories\ManagementHierarchyRepository;

class MakeBranchMainHandler
{
    public function __construct(
        private ManagementHierarchyRepository $repository,
    ) {
    }

    public function handle(MakeBranchMainCommand $makeBranchMainCommand)
    {
          $this->repository->makeMainBranch($makeBranchMainCommand->getId(), $makeBranchMainCommand->getBranchAlternativeId());
    }
}
