<?php

declare(strict_types=1);

namespace Modules\Auth\Handlers;

use Modules\Auth\Repositories\AuthRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteAuthHandler
{
    public function __construct(
        private AuthRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteAuth($id);
    }
}
