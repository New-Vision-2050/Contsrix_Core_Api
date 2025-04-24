<?php

declare(strict_types=1);

namespace Modules\Shared\JobType\Handlers;

use Modules\Shared\JobType\Commands\UpdateJobTypeCommand;
use Modules\Shared\JobType\Repositories\JobTypeRepository;

class UpdateJobTypeHandler
{
    public function __construct(
        private JobTypeRepository $repository,
    ) {
    }

    public function handle(UpdateJobTypeCommand $updateJobTypeCommand)
    {
        $this->repository->updateJobType($updateJobTypeCommand->getId(), $updateJobTypeCommand->toArray());
    }
}
