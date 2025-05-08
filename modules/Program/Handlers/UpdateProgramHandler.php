<?php

declare(strict_types=1);

namespace Modules\Program\Handlers;

use Modules\Program\Commands\UpdateProgramCommand;
use Modules\Program\Repositories\ProgramRepository;

class UpdateProgramHandler
{
    public function __construct(
        private ProgramRepository $repository,
    ) {
    }

    public function handle(UpdateProgramCommand $updateProgramCommand)
    {
        $this->repository->updateProgram($updateProgramCommand->getId(), $updateProgramCommand->toArray());
    }
}
