<?php

declare(strict_types=1);

namespace Modules\Shared\RightTerminate\Handlers;

use Modules\Shared\RightTerminate\Repositories\RightTerminateRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteRightTerminateHandler
{
    public function __construct(
        private RightTerminateRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteRightTerminate($id);
    }
}
