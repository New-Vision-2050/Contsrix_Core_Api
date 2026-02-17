<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Handlers;

use Modules\Project\ProjectType\Commands\UpdateProjectTypeCommand;
use Modules\Project\ProjectType\Repositories\ProjectTypeRepository;

class UpdateProjectTypeHandler
{
    public function __construct(
        private ProjectTypeRepository $repository,
    ) {
    }

    public function handle(UpdateProjectTypeCommand $updateProjectTypeCommand)
    {
        $this->repository->updateProjectType($updateProjectTypeCommand->getId(), $updateProjectTypeCommand->toArray());
    }
}
