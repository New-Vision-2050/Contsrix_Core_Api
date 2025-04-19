<?php

declare(strict_types=1);

namespace Modules\Shared\University\Handlers;

use Modules\Shared\University\Repositories\UniversityRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteUniversityHandler
{
    public function __construct(
        private UniversityRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteUniversity($id);
    }
}
