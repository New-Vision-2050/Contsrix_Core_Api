<?php

declare(strict_types=1);

namespace Modules\Program\Handlers;

use Modules\Program\Repositories\ProgramRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteProgramHandler
{
    public function __construct(
        private ProgramRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteProgram($id);
    }
}
