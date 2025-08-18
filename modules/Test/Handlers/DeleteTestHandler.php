<?php

declare(strict_types=1);

namespace Modules\Test\Handlers;

use Modules\Test\Repositories\TestRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteTestHandler
{
    public function __construct(
        private TestRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteTest($id);
    }
}
