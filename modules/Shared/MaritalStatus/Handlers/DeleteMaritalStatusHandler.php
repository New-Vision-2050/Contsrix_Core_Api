<?php

declare(strict_types=1);

namespace Modules\Shared\MaritalStatus\Handlers;

use Modules\Shared\MaritalStatus\Repositories\MaritalStatusRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteMaritalStatusHandler
{
    public function __construct(
        private MaritalStatusRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteMaritalStatus($id);
    }
}
