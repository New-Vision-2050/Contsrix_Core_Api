<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Handlers;

use Modules\Company\ManagementHierarchy\Commands\UpdateManagementCommand;
use Modules\Company\ManagementHierarchy\Repositories\ManagementHierarchyRepository;

class UpdateManagementHandler
{
    public function __construct(
        private ManagementHierarchyRepository $repository
    ) {
    }

    public function handle(UpdateManagementCommand $command): void
    {
        $this->repository->updateManagement(
            $command->getId(),
            $command->managementToArray(),
            $command->managementDetailToArray(),
            $command->getDeputyManagerIds()
        );
    }
}
