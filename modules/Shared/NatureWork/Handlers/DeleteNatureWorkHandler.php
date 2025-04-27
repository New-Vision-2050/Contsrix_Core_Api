<?php

declare(strict_types=1);

namespace Modules\Shared\NatureWork\Handlers;

use Modules\Shared\NatureWork\Repositories\NatureWorkRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteNatureWorkHandler
{
    public function __construct(
        private NatureWorkRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteNatureWork($id);
    }
}
