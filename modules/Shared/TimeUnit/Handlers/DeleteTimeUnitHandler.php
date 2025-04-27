<?php

declare(strict_types=1);

namespace Modules\Shared\TimeUnit\Handlers;

use Modules\Shared\TimeUnit\Repositories\TimeUnitRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteTimeUnitHandler
{
    public function __construct(
        private TimeUnitRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteTimeUnit($id);
    }
}
