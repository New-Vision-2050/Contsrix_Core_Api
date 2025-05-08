<?php

declare(strict_types=1);

namespace Modules\Shared\JobType\Handlers;

use Modules\Shared\JobType\Commands\ChangeJobTypeStatusCommand;
use Modules\Shared\JobType\Repositories\JobTypeRepository;

class ChangeJobTypeStatusHandler
{
    public function __construct(
        private JobTypeRepository $repository,
    ) {
    }

    public function handle(ChangeJobTypeStatusCommand $changeJobTypeStatusCommand)
    {
        $this->repository->updateJobType($changeJobTypeStatusCommand->getId(), $changeJobTypeStatusCommand->toArray());
    }
}
