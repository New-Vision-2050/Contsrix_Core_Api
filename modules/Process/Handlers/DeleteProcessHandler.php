<?php

declare(strict_types=1);

namespace Modules\Process\Handlers;

use Modules\Process\Repositories\ProcessRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteProcessHandler
{
    public function __construct(
        private ProcessRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteProcess($id);
    }
}
