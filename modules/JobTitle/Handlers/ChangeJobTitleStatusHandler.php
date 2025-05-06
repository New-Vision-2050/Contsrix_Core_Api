<?php

declare(strict_types=1);

namespace Modules\JobTitle\Handlers;

use Modules\JobTitle\Commands\ChangeJobTitleStatusCommand;
use Modules\JobTitle\Repositories\JobTitleRepository;

class ChangeJobTitleStatusHandler
{
    public function __construct(
        private JobTitleRepository $repository,
    ) {
    }

    public function handle(ChangeJobTitleStatusCommand $changeJobTitleStatusCommand)
    {
        $this->repository->updateJobTitle($changeJobTitleStatusCommand->getId(), $changeJobTitleStatusCommand->toArray());
    }
}
