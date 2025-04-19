<?php

declare(strict_types=1);

namespace Modules\Shared\TypeAllowance\Handlers;

use Modules\Shared\TypeAllowance\Repositories\TypeAllowanceRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteTypeAllowanceHandler
{
    public function __construct(
        private TypeAllowanceRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteTypeAllowance($id);
    }
}
