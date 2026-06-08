<?php

declare(strict_types=1);

namespace Modules\Shared/Process\Handlers;

use Modules\Shared/Process\Repositories\Shared/ProcessRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteShared/ProcessHandler
{
    public function __construct(
        private Shared/ProcessRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteShared/Process($id);
    }
}
