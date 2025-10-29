<?php

declare(strict_types=1);

namespace Modules\Unit\Handlers;

use Modules\Unit\Repositories\UnitRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteUnitHandler
{
    public function __construct(
        private UnitRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteUnit($id);
    }
}
