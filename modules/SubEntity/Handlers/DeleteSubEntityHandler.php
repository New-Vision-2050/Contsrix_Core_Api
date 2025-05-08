<?php

declare(strict_types=1);

namespace Modules\SubEntity\Handlers;

use Modules\SubEntity\Repositories\SubEntityRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteSubEntityHandler
{
    public function __construct(
        private SubEntityRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteSubEntity($id);
    }
}
