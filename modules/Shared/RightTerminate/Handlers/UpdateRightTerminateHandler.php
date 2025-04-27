<?php

declare(strict_types=1);

namespace Modules\Shared\RightTerminate\Handlers;

use Modules\Shared\RightTerminate\Commands\UpdateRightTerminateCommand;
use Modules\Shared\RightTerminate\Repositories\RightTerminateRepository;

class UpdateRightTerminateHandler
{
    public function __construct(
        private RightTerminateRepository $repository,
    ) {
    }

    public function handle(UpdateRightTerminateCommand $updateRightTerminateCommand)
    {
        $this->repository->updateRightTerminate($updateRightTerminateCommand->getId(), $updateRightTerminateCommand->toArray());
    }
}
