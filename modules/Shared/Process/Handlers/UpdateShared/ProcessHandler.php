<?php

declare(strict_types=1);

namespace Modules\Shared/Process\Handlers;

use Modules\Shared/Process\Commands\UpdateShared/ProcessCommand;
use Modules\Shared/Process\Repositories\Shared/ProcessRepository;

class UpdateShared/ProcessHandler
{
    public function __construct(
        private Shared/ProcessRepository $repository,
    ) {
    }

    public function handle(UpdateShared/ProcessCommand $updateShared/ProcessCommand)
    {
        $this->repository->updateShared/Process($updateShared/ProcessCommand->getId(), $updateShared/ProcessCommand->toArray());
    }
}
