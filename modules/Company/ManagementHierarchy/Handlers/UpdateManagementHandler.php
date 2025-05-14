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
        $managementData = [
            'name' => $command->getName(),
//            'parent_id' => $command->getBranchId(),
            'company_id' => $command->getCompanyId(),
            'is_active' => $command->getIsActive(),
            'manager_id' => $command->getManagerId(),
        ];

        $managementDetail = [
            'description' => $command->getDescription(),
            'reference_user_id' => $command->getReferenceUserId(),
        ];

        $this->repository->updateManagement(
            $command->getId(),
            $managementData,
            $managementDetail,
            $command->getDeputyManagerIds()
        );
    }
}
