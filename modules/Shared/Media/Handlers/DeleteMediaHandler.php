<?php

declare(strict_types=1);

namespace Modules\Shared\Media\Handlers;

use Modules\Shared\Media\Repositories\MediaRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteMediaHandler
{
    public function __construct(
        private MediaRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteMedia($id);
    }
}
