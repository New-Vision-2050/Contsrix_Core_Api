<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Handlers;

use Modules\Project\ProjectType\Commands\UpdateSecondLevelProjectTypeCommand;
use Modules\Project\ProjectType\Repositories\ProjectTypeRepository;

class UpdateSecondLevelProjectTypeHandler
{
    public function __construct(
        private ProjectTypeRepository $repository,
    ) {}

    public function handle(UpdateSecondLevelProjectTypeCommand $command): void
    {
        $this->repository->updateSecondLevelProjectType(
            $command->getId(),
            $command->toArray(),
            $command->getSchemaIds()
        );
    }
}
