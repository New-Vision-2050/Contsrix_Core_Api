<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Handlers;

use Modules\Project\ProjectManagement\Commands\UpdateProjectManagementCommand;
use Modules\Project\ProjectManagement\Repositories\ProjectManagementRepository;

class UpdateProjectManagementHandler
{
    public function __construct(
        private ProjectManagementRepository $repository,
    ) {
    }

    public function handle(UpdateProjectManagementCommand $updateProjectManagementCommand)
    {
        $this->repository->updateProjectManagement($updateProjectManagementCommand->getId(), $updateProjectManagementCommand->toArray());
    }
}
