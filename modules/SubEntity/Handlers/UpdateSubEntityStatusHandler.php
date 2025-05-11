<?php

declare(strict_types=1);

namespace Modules\SubEntity\Handlers;

use Modules\SubEntity\Commands\UpdateSubEntityStatusCommand;
use Modules\SubEntity\Repositories\SubEntityRepository;

class UpdateSubEntityStatusHandler
{
    public function __construct(
        private SubEntityRepository $repository,
    ) {
    }

    public function handle(UpdateSubEntityStatusCommand $updateSubEntityStatusCommand)
    {
        $this->repository->updateSubEntityStatus($updateSubEntityStatusCommand->getId(), $updateSubEntityStatusCommand->toArray());
    }
}
