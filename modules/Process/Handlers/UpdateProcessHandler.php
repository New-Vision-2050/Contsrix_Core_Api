<?php

declare(strict_types=1);

namespace Modules\Process\Handlers;

use Modules\Process\Commands\UpdateProcessCommand;
use Modules\Process\Repositories\ProcessRepository;

class UpdateProcessHandler
{
    public function __construct(
        private ProcessRepository $repository,
    ) {
    }

    public function handle(UpdateProcessCommand $updateProcessCommand)
    {
        $this->repository->updateProcess($updateProcessCommand->getId(), $updateProcessCommand->toArray());
    }
}
