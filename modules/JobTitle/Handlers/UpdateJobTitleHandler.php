<?php

declare(strict_types=1);

namespace Modules\JobTitle\Handlers;

use Modules\JobTitle\Commands\UpdateJobTitleCommand;
use Modules\JobTitle\Repositories\JobTitleRepository;

class UpdateJobTitleHandler
{
    public function __construct(
        private JobTitleRepository $repository,
    ) {
    }

    public function handle(UpdateJobTitleCommand $updateJobTitleCommand)
    {
        $this->repository->updateJobTitle($updateJobTitleCommand->getId(), $updateJobTitleCommand->toArray());
    }
}
